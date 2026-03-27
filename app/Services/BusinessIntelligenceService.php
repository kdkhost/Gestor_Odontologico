<?php

namespace App\Services;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\CommissionEntry;
use App\Models\CommissionSettlement;
use App\Models\Patient;
use App\Models\PerformanceTarget;
use App\Models\Unit;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BusinessIntelligenceService
{
    public function snapshot(?int $unitId, string|CarbonInterface|null $fromDate, string|CarbonInterface|null $toDate): array
    {
        $unitId = $this->resolveScopeUnitId($unitId);
        [$start, $end] = $this->resolvePeriod($fromDate, $toDate);
        $summary = $this->summary($unitId, $start, $end);
        $targets = $this->targets($unitId, $start, $end);

        return [
            'scope' => [
                'unit_id' => $unitId,
                'label' => $this->resolveScopeLabel($unitId),
            ],
            'period' => [
                'from' => $start,
                'to' => $end,
                'days' => $start->diffInDays($end) + 1,
            ],
            'summary' => $summary,
            'targets' => $targets
                ->where('scope_type', '!=', 'professional')
                ->values()
                ->all(),
            'professional_targets' => $targets
                ->where('scope_type', 'professional')
                ->values()
                ->all(),
            'professionals' => $this->professionalLeaderboard($unitId, $start, $end),
            'units' => $this->unitBreakdown($unitId, $start, $end),
            'recent_commissions' => $this->recentCommissions($unitId, $start, $end),
        ];
    }

    public function exportPayload(string $section, ?int $unitId, string|CarbonInterface|null $fromDate, string|CarbonInterface|null $toDate): array
    {
        $snapshot = $this->snapshot($unitId, $fromDate, $toDate);
        $from = $snapshot['period']['from']->format('Ymd');
        $to = $snapshot['period']['to']->format('Ymd');

        return match ($section) {
            'summary' => [
                'filename' => "bi-resumo-{$from}-{$to}.csv",
                'headers' => ['escopo', 'de', 'ate', 'receita_recebida', 'contas_geradas', 'atendimentos_concluidos', 'novos_pacientes', 'ticket_medio', 'comissao_gerada', 'comissao_pendente', 'comissao_paga', 'repasse_pago', 'repasse_conciliado', 'repasse_aguardando_conciliacao'],
                'rows' => [[
                    $snapshot['scope']['label'],
                    $snapshot['period']['from']->format('d/m/Y'),
                    $snapshot['period']['to']->format('d/m/Y'),
                    $this->formatDecimal($snapshot['summary']['revenue_received']),
                    $this->formatDecimal($snapshot['summary']['receivables_created']),
                    (string) $snapshot['summary']['completed_appointments'],
                    (string) $snapshot['summary']['new_patients'],
                    $this->formatDecimal($snapshot['summary']['average_ticket']),
                    $this->formatDecimal($snapshot['summary']['commission_generated']),
                    $this->formatDecimal($snapshot['summary']['commission_pending']),
                    $this->formatDecimal($snapshot['summary']['commission_paid']),
                    $this->formatDecimal($snapshot['summary']['settlements_paid']),
                    $this->formatDecimal($snapshot['summary']['settlements_reconciled']),
                    $this->formatDecimal($snapshot['summary']['settlements_pending_reconciliation']),
                ]],
            ],
            'professionals' => [
                'filename' => "bi-profissionais-{$from}-{$to}.csv",
                'headers' => ['profissional', 'unidade', 'atendimentos_concluidos', 'receita_recebida', 'comissao_gerada', 'comissao_pendente'],
                'rows' => $snapshot['professionals']->map(fn (array $row) => [
                    $row['professional_name'],
                    $row['unit_name'],
                    (string) $row['completed_appointments'],
                    $this->formatDecimal($row['revenue_received']),
                    $this->formatDecimal($row['commission_generated']),
                    $this->formatDecimal($row['commission_pending']),
                ])->all(),
            ],
            'targets' => [
                'filename' => "bi-metas-{$from}-{$to}.csv",
                'headers' => ['tipo_escopo', 'metrica', 'escopo', 'meta', 'realizado', 'progresso_percentual', 'periodo'],
                'rows' => collect($snapshot['targets'])
                    ->merge($snapshot['professional_targets'] ?? [])
                    ->map(fn (array $row) => [
                        $row['scope_type'],
                        $row['label'],
                        $row['scope_label'],
                        $this->formatDecimal($row['target_value']),
                        $this->formatDecimal($row['current_value']),
                        $this->formatDecimal($row['progress']),
                        $row['period_label'],
                    ])->all(),
            ],
            default => throw new InvalidArgumentException('Seção de exportação inválida.'),
        };
    }

    private function summary(?int $unitId, Carbon $start, Carbon $end): array
    {
        $revenueReceived = (float) $this->scopeQuery(AccountReceivable::query(), $unitId)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('net_amount');

        $receivablesCreated = (float) $this->scopeQuery(AccountReceivable::query(), $unitId)
            ->whereBetween('created_at', [$start, $end])
            ->sum('net_amount');

        $completedAppointments = $this->scopeQuery(Appointment::query(), $unitId)
            ->where('status', 'completed')
            ->whereBetween('scheduled_start', [$start, $end])
            ->count();

        $newPatients = $this->scopeQuery(Patient::query(), $unitId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $paidAccounts = $this->scopeQuery(AccountReceivable::query(), $unitId)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->count();

        $commissionGenerated = (float) $this->scopeQuery(CommissionEntry::query(), $unitId)
            ->whereNotNull('calculated_at')
            ->whereBetween('calculated_at', [$start, $end])
            ->sum('amount');

        $commissionPending = (float) $this->scopeQuery(CommissionEntry::query(), $unitId)
            ->whereIn('status', ['pending', 'batched'])
            ->sum('amount');

        $commissionPaid = (float) $this->scopeQuery(CommissionEntry::query(), $unitId)
            ->where('status', 'paid')
            ->sum('amount');

        $settlementsPaid = (float) $this->scopeQuery(CommissionSettlement::query(), $unitId)
            ->where('status', 'paid')
            ->sum('gross_amount');

        $settlementsReconciled = (float) $this->scopeQuery(CommissionSettlement::query(), $unitId)
            ->whereNotNull('reconciled_at')
            ->sum('gross_amount');

        $settlementsPendingReconciliation = (float) $this->scopeQuery(CommissionSettlement::query(), $unitId)
            ->where('status', 'paid')
            ->whereNull('reconciled_at')
            ->sum('gross_amount');

        return [
            'revenue_received' => round($revenueReceived, 2),
            'receivables_created' => round($receivablesCreated, 2),
            'completed_appointments' => $completedAppointments,
            'new_patients' => $newPatients,
            'average_ticket' => $paidAccounts > 0 ? round($revenueReceived / $paidAccounts, 2) : 0.0,
            'commission_generated' => round($commissionGenerated, 2),
            'commission_pending' => round($commissionPending, 2),
            'commission_paid' => round($commissionPaid, 2),
            'settlements_paid' => round($settlementsPaid, 2),
            'settlements_reconciled' => round($settlementsReconciled, 2),
            'settlements_pending_reconciliation' => round($settlementsPendingReconciliation, 2),
        ];
    }

    private function targets(?int $unitId, Carbon $start, Carbon $end): Collection
    {
        return PerformanceTarget::query()
            ->with(['unit', 'professional.user', 'professional.unit'])
            ->where('is_active', true)
            ->whereDate('period_start', '<=', $end->toDateString())
            ->whereDate('period_end', '>=', $start->toDateString())
            ->when($unitId !== null, function (Builder $query) use ($unitId) {
                $query->where(function (Builder $builder) use ($unitId) {
                    $builder->whereNull('unit_id')
                        ->orWhere('unit_id', $unitId)
                        ->orWhereHas('professional', fn (Builder $professionalQuery) => $professionalQuery->where('unit_id', $unitId));
                });
            })
            ->orderByRaw('case when professional_id is null then 0 else 1 end')
            ->orderBy('metric')
            ->get()
            ->map(function (PerformanceTarget $target) use ($start, $end): array {
                $currentValue = $this->metricValueForTarget($target, $start, $end);
                $progress = (float) $target->target_value > 0
                    ? round(($currentValue / (float) $target->target_value) * 100, 1)
                    : 0.0;
                $scopeType = $target->professional_id
                    ? 'professional'
                    : ($target->unit_id ? 'unit' : 'global');

                return [
                    'label' => $this->metricLabel($target->metric),
                    'metric' => $target->metric,
                    'scope_type' => $scopeType,
                    'scope_label' => $target->professional?->user?->name
                        ?? $target->unit?->name
                        ?? 'Todas as unidades',
                    'scope_secondary_label' => $target->professional?->unit?->name,
                    'target_value' => (float) $target->target_value,
                    'current_value' => $currentValue,
                    'progress' => $progress,
                    'period_label' => $target->period_start->format('d/m/Y').' - '.$target->period_end->format('d/m/Y'),
                    'notes' => $target->notes,
                ];
            });
    }

    private function professionalLeaderboard(?int $unitId, Carbon $start, Carbon $end): Collection
    {
        $appointments = $this->scopeQuery(
            Appointment::query()->with(['professional.user', 'professional.unit']),
            $unitId,
        )
            ->whereNotNull('professional_id')
            ->where('status', 'completed')
            ->whereBetween('scheduled_start', [$start, $end])
            ->get();

        $revenues = $this->scopeQuery(
            AccountReceivable::query()->with([
                'appointment.professional.user',
                'appointment.professional.unit',
                'treatmentPlan.professional.user',
                'treatmentPlan.professional.unit',
            ]),
            $unitId,
        )
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->get();

        $commissions = $this->scopeQuery(
            CommissionEntry::query()->with(['professional.user', 'professional.unit']),
            $unitId,
        )
            ->whereNotNull('calculated_at')
            ->whereBetween('calculated_at', [$start, $end])
            ->get();

        $rows = collect();

        foreach ($appointments as $appointment) {
            $professional = $appointment->professional;

            if (! $professional) {
                continue;
            }

            $key = (string) $professional->id;
            $current = $rows->get($key, $this->blankProfessionalRow($professional));
            $current['completed_appointments']++;
            $rows->put($key, $current);
        }

        foreach ($revenues as $account) {
            $professional = $account->appointment?->professional ?? $account->treatmentPlan?->professional;

            if (! $professional) {
                continue;
            }

            $key = (string) $professional->id;
            $current = $rows->get($key, $this->blankProfessionalRow($professional));
            $current['revenue_received'] = round($current['revenue_received'] + (float) $account->net_amount, 2);
            $rows->put($key, $current);
        }

        foreach ($commissions as $commission) {
            $professional = $commission->professional;

            if (! $professional) {
                continue;
            }

            $key = (string) $professional->id;
            $current = $rows->get($key, $this->blankProfessionalRow($professional));
            $current['commission_generated'] = round($current['commission_generated'] + (float) $commission->amount, 2);

            if (in_array($commission->status, ['pending', 'batched'], true)) {
                $current['commission_pending'] = round($current['commission_pending'] + (float) $commission->amount, 2);
            }

            $rows->put($key, $current);
        }

        return $rows
            ->sortByDesc(fn (array $row) => [$row['revenue_received'], $row['completed_appointments']])
            ->values();
    }

    private function unitBreakdown(?int $unitId, Carbon $start, Carbon $end): Collection
    {
        $units = $unitId !== null
            ? Unit::query()->whereKey($unitId)->get()
            : Unit::query()->orderBy('name')->get();

        return $units->map(function (Unit $unit) use ($start, $end): array {
            $revenue = (float) AccountReceivable::query()
                ->where('unit_id', $unit->id)
                ->whereNotNull('paid_at')
                ->whereBetween('paid_at', [$start, $end])
                ->sum('net_amount');

            $appointments = Appointment::query()
                ->where('unit_id', $unit->id)
                ->where('status', 'completed')
                ->whereBetween('scheduled_start', [$start, $end])
                ->count();

            return [
                'unit_name' => $unit->name,
                'revenue_received' => round($revenue, 2),
                'completed_appointments' => $appointments,
            ];
        })->values();
    }

    private function recentCommissions(?int $unitId, Carbon $start, Carbon $end): Collection
    {
        return $this->scopeQuery(
            CommissionEntry::query()->with(['professional.user', 'accountReceivable.patient', 'unit']),
            $unitId,
        )
            ->whereNotNull('calculated_at')
            ->whereBetween('calculated_at', [$start, $end])
            ->latest('calculated_at')
            ->limit(12)
            ->get();
    }

    private function metricValueForTarget(PerformanceTarget $target, Carbon $start, Carbon $end): float
    {
        return $this->metricValueForScope(
            metric: $target->metric,
            start: $start,
            end: $end,
            unitId: $target->unit_id,
            professionalId: $target->professional_id,
        );
    }

    private function metricValueForScope(string $metric, Carbon $start, Carbon $end, ?int $unitId = null, ?int $professionalId = null): float
    {
        return match ($metric) {
            'revenue_received' => $this->revenueReceivedValue($start, $end, $unitId, $professionalId),
            'completed_appointments' => $this->completedAppointmentsValue($start, $end, $unitId, $professionalId),
            'new_patients' => $this->newPatientsValue($start, $end, $unitId, $professionalId),
            'commission_generated' => $this->commissionGeneratedValue($start, $end, $unitId, $professionalId),
            default => 0.0,
        };
    }

    private function revenueReceivedValue(Carbon $start, Carbon $end, ?int $unitId = null, ?int $professionalId = null): float
    {
        $query = $this->scopeQuery(AccountReceivable::query(), $unitId)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end]);

        if ($professionalId !== null) {
            $query->where(function (Builder $builder) use ($professionalId) {
                $builder->whereHas('appointment', fn (Builder $appointmentQuery) => $appointmentQuery->where('professional_id', $professionalId))
                    ->orWhereHas('treatmentPlan', fn (Builder $planQuery) => $planQuery->where('professional_id', $professionalId));
            });
        }

        return round((float) $query->sum('net_amount'), 2);
    }

    private function completedAppointmentsValue(Carbon $start, Carbon $end, ?int $unitId = null, ?int $professionalId = null): float
    {
        $query = $this->scopeQuery(Appointment::query(), $unitId)
            ->where('status', 'completed')
            ->whereBetween('scheduled_start', [$start, $end]);

        if ($professionalId !== null) {
            $query->where('professional_id', $professionalId);
        }

        return (float) $query->count();
    }

    private function newPatientsValue(Carbon $start, Carbon $end, ?int $unitId = null, ?int $professionalId = null): float
    {
        if ($professionalId === null) {
            return (float) $this->scopeQuery(Patient::query(), $unitId)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        return (float) $this->scopeQuery(Appointment::query(), $unitId)
            ->where('professional_id', $professionalId)
            ->where('status', 'completed')
            ->select('patient_id')
            ->selectRaw('min(scheduled_start) as first_seen_at')
            ->groupBy('patient_id')
            ->havingRaw('min(scheduled_start) between ? and ?', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->get()
            ->count();
    }

    private function commissionGeneratedValue(Carbon $start, Carbon $end, ?int $unitId = null, ?int $professionalId = null): float
    {
        $query = $this->scopeQuery(CommissionEntry::query(), $unitId)
            ->whereNotNull('calculated_at')
            ->whereBetween('calculated_at', [$start, $end]);

        if ($professionalId !== null) {
            $query->where('professional_id', $professionalId);
        }

        return round((float) $query->sum('amount'), 2);
    }

    public function metricOptions(): array
    {
        return [
            'revenue_received' => 'Receita recebida',
            'completed_appointments' => 'Atendimentos concluídos',
            'new_patients' => 'Novos pacientes',
            'commission_generated' => 'Comissão gerada',
        ];
    }

    private function metricLabel(string $metric): string
    {
        return $this->metricOptions()[$metric] ?? $metric;
    }

    private function blankProfessionalRow($professional): array
    {
        return [
            'professional_id' => $professional->id,
            'professional_name' => $professional->user?->name ?? 'Sem nome',
            'unit_name' => $professional->unit?->name ?? 'Sem unidade',
            'completed_appointments' => 0,
            'revenue_received' => 0.0,
            'commission_generated' => 0.0,
            'commission_pending' => 0.0,
        ];
    }

    private function resolveScopeUnitId(?int $unitId = null): ?int
    {
        if ($unitId !== null) {
            return $unitId;
        }

        $user = auth()->user();

        if (! $user || (method_exists($user, 'hasRole') && $user->hasRole('superadmin'))) {
            return null;
        }

        return $user->unit_id;
    }

    private function resolveScopeLabel(?int $unitId): string
    {
        if ($unitId === null) {
            return 'Todas as unidades';
        }

        return Unit::query()->find($unitId)?->name ?? "Unidade #{$unitId}";
    }

    private function resolvePeriod(string|CarbonInterface|null $fromDate, string|CarbonInterface|null $toDate): array
    {
        $start = $fromDate instanceof CarbonInterface
            ? Carbon::instance($fromDate)->startOfDay()
            : Carbon::parse($fromDate ?: now(config('app.timezone'))->subDays(29)->toDateString(), config('app.timezone'))->startOfDay();

        $end = $toDate instanceof CarbonInterface
            ? Carbon::instance($toDate)->endOfDay()
            : Carbon::parse($toDate ?: now(config('app.timezone'))->toDateString(), config('app.timezone'))->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function scopeQuery(Builder $query, ?int $unitId, string $column = 'unit_id'): Builder
    {
        if ($unitId !== null) {
            $query->where($column, $unitId);
        }

        return $query;
    }

    private function formatDecimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
