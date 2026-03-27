<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AutomationRunLog;
use App\Models\NotificationTemplate;
use App\Models\Patient;
use App\Models\PaymentInstallment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class OperationalAutomationService
{
    public function __construct(
        private readonly EvolutionWhatsAppService $whatsapp,
        private readonly GreetingService $greetings,
        private readonly SettingService $settings,
    ) {}

    public function runAll(bool $dryRun = false): array
    {
        return [
            'appointment_reminder' => $this->runAppointmentReminders($dryRun),
            'financial_due' => $this->runFinancialDue($dryRun),
            'patient_reactivation' => $this->runPatientReactivation($dryRun),
        ];
    }

    public function latestLogs(int $limit = 12): Collection
    {
        return AutomationRunLog::query()
            ->latest('started_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    private function runAppointmentReminders(bool $dryRun): array
    {
        return $this->runAutomation('appointment_reminder', $dryRun, function () use ($dryRun) {
            $hoursBefore = $this->settingInt('appointment_reminder_hours_before');
            $start = now(config('app.timezone'));
            $end = $start->copy()->addHours($hoursBefore);
            $template = $this->templateForEvent('appointment.reminder');

            $appointments = Appointment::query()
                ->with(['patient', 'unit', 'procedure', 'professional.user'])
                ->where('status', 'confirmed')
                ->whereNull('reminder_sent_at')
                ->whereBetween('scheduled_start', [$start, $end])
                ->orderBy('scheduled_start')
                ->get();

            $summary = $this->baseSummary($dryRun, $appointments);

            foreach ($appointments as $appointment) {
                if (! $template || ! $appointment->patient?->whatsapp || ! $appointment->patient?->whatsapp_opt_in) {
                    $summary['skipped_count']++;

                    continue;
                }

                if ($dryRun) {
                    continue;
                }

                $response = $this->whatsapp->sendTemplate($template, $appointment->patient->whatsapp, [
                    'saudacao' => $this->greetings->current(),
                    'paciente_nome' => $appointment->patient->preferred_name ?: $appointment->patient->name,
                    'data_consulta' => optional($appointment->scheduled_start)->format('d/m/Y'),
                    'hora_consulta' => optional($appointment->scheduled_start)->format('H:i'),
                    'unidade_nome' => $appointment->unit?->name ?? 'clínica',
                    'procedimento_nome' => $appointment->procedure?->name ?? 'consulta',
                ], [
                    'opt_in_confirmed' => $appointment->patient->whatsapp_opt_in,
                ]);

                if ($response['ok']) {
                    $appointment->update(['reminder_sent_at' => now()]);
                    $summary['sent_count']++;
                } else {
                    $summary[$response['status'] === 'failed' ? 'failed_count' : 'skipped_count']++;
                }
            }

            return $summary;
        });
    }

    private function runFinancialDue(bool $dryRun): array
    {
        return $this->runAutomation('financial_due', $dryRun, function () use ($dryRun) {
            $daysBefore = $this->settingInt('financial_due_days_before');
            $cutoff = now(config('app.timezone'))->copy()->addDays($daysBefore)->toDateString();
            $template = $this->templateForEvent('financial.installment_due');

            $installments = PaymentInstallment::query()
                ->with(['accountReceivable.patient', 'accountReceivable.unit'])
                ->whereIn('status', ['open', 'overdue'])
                ->whereDate('due_date', '<=', $cutoff)
                ->orderBy('due_date')
                ->get()
                ->filter(fn (PaymentInstallment $installment) => ! $this->hasRecentReminder($installment->meta, 'last_financial_reminder_at', 1))
                ->values();

            $summary = $this->baseSummary($dryRun, $installments);

            foreach ($installments as $installment) {
                $patient = $installment->accountReceivable?->patient;

                if (! $template || ! $patient?->whatsapp || ! $patient->whatsapp_opt_in) {
                    $summary['skipped_count']++;

                    continue;
                }

                if ($dryRun) {
                    continue;
                }

                $response = $this->whatsapp->sendTemplate($template, $patient->whatsapp, [
                    'saudacao' => $this->greetings->current(),
                    'paciente_nome' => $patient->preferred_name ?: $patient->name,
                    'valor' => 'R$ '.number_format((float) $installment->amount, 2, ',', '.'),
                    'vencimento' => optional($installment->due_date)->format('d/m/Y'),
                    'unidade_nome' => $installment->accountReceivable?->unit?->name ?? 'clínica',
                ], [
                    'opt_in_confirmed' => $patient->whatsapp_opt_in,
                ]);

                if ($response['ok']) {
                    $installment->update([
                        'meta' => array_merge($installment->meta ?? [], [
                            'last_financial_reminder_at' => now()->toIso8601String(),
                        ]),
                    ]);
                    $summary['sent_count']++;
                } else {
                    $summary[$response['status'] === 'failed' ? 'failed_count' : 'skipped_count']++;
                }
            }

            return $summary;
        });
    }

    private function runPatientReactivation(bool $dryRun): array
    {
        return $this->runAutomation('patient_reactivation', $dryRun, function () use ($dryRun) {
            $afterDays = $this->settingInt('patient_reactivation_after_days');
            $cooldownDays = $this->settingInt('reactivation_cooldown_days');
            $template = $this->templateForEvent('patient.reactivation');

            $patients = Patient::query()
                ->with(['unit'])
                ->where('is_active', true)
                ->where('whatsapp_opt_in', true)
                ->whereNotNull('last_visit_at')
                ->where('last_visit_at', '<=', now(config('app.timezone'))->copy()->subDays($afterDays))
                ->whereDoesntHave('appointments', function ($query) {
                    $query
                        ->where('scheduled_start', '>=', now(config('app.timezone')))
                        ->whereNotIn('status', ['cancelled', 'no_show']);
                })
                ->orderBy('last_visit_at')
                ->get()
                ->filter(fn (Patient $patient) => ! $this->hasRecentReminder($patient->meta, 'last_reactivation_notified_at', $cooldownDays))
                ->values();

            $summary = $this->baseSummary($dryRun, $patients);

            foreach ($patients as $patient) {
                if (! $template || ! $patient->whatsapp) {
                    $summary['skipped_count']++;

                    continue;
                }

                if ($dryRun) {
                    continue;
                }

                $response = $this->whatsapp->sendTemplate($template, $patient->whatsapp, [
                    'saudacao' => $this->greetings->current(),
                    'paciente_nome' => $patient->preferred_name ?: $patient->name,
                    'unidade_nome' => $patient->unit?->name ?? 'clínica',
                    'ultima_visita' => optional($patient->last_visit_at)->format('d/m/Y'),
                ], [
                    'opt_in_confirmed' => $patient->whatsapp_opt_in,
                ]);

                if ($response['ok']) {
                    $patient->update([
                        'meta' => array_merge($patient->meta ?? [], [
                            'last_reactivation_notified_at' => now()->toIso8601String(),
                        ]),
                    ]);
                    $summary['sent_count']++;
                } else {
                    $summary[$response['status'] === 'failed' ? 'failed_count' : 'skipped_count']++;
                }
            }

            return $summary;
        });
    }

    private function runAutomation(string $type, bool $dryRun, callable $callback): array
    {
        if (! $this->settingBool("{$type}_enabled")) {
            return $this->persistLog($type, [
                'status' => 'disabled',
                'matched_count' => 0,
                'sent_count' => 0,
                'skipped_count' => 0,
                'failed_count' => 0,
                'payload' => ['dry_run' => $dryRun],
            ]);
        }

        $log = AutomationRunLog::query()->create([
            'automation_type' => $type,
            'status' => $dryRun ? 'preview' : 'running',
            'started_at' => now(),
            'payload' => ['dry_run' => $dryRun],
        ]);

        try {
            $summary = $callback();

            $log->update([
                'status' => $dryRun ? 'preview' : 'completed',
                'matched_count' => $summary['matched_count'],
                'sent_count' => $summary['sent_count'],
                'skipped_count' => $summary['skipped_count'],
                'failed_count' => $summary['failed_count'],
                'payload' => $summary['payload'],
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $log->update([
                'status' => 'failed',
                'failed_count' => 1,
                'payload' => [
                    'dry_run' => $dryRun,
                    'error' => $exception->getMessage(),
                ],
                'finished_at' => now(),
            ]);

            throw $exception;
        }

        return $log->fresh()->toArray();
    }

    private function persistLog(string $type, array $attributes): array
    {
        return AutomationRunLog::query()->create([
            'automation_type' => $type,
            'started_at' => now(),
            'finished_at' => now(),
            ...$attributes,
        ])->toArray();
    }

    private function baseSummary(bool $dryRun, Collection $items): array
    {
        return [
            'matched_count' => $items->count(),
            'sent_count' => 0,
            'skipped_count' => 0,
            'failed_count' => 0,
            'payload' => [
                'dry_run' => $dryRun,
                'sample_ids' => $items->take(10)->map(fn ($item) => $item->id)->values()->all(),
            ],
        ];
    }

    private function settingBool(string $key): bool
    {
        return (bool) $this->settings->get('automation', $key, config("clinic.automation.{$key}"));
    }

    private function settingInt(string $key): int
    {
        return (int) $this->settings->get('automation', $key, config("clinic.automation.{$key}"));
    }

    private function templateForEvent(string $event): ?NotificationTemplate
    {
        return NotificationTemplate::query()
            ->where('channel', 'whatsapp')
            ->where('trigger_event', $event)
            ->where('is_active', true)
            ->first();
    }

    private function hasRecentReminder(?array $meta, string $key, int $days): bool
    {
        $value = data_get($meta, $key);

        if (! $value) {
            return false;
        }

        try {
            return Carbon::parse($value, config('app.timezone'))
                ->greaterThanOrEqualTo(now(config('app.timezone'))->subDays($days));
        } catch (Throwable) {
            return false;
        }
    }
}
