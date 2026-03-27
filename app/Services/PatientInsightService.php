<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\Patient;

class PatientInsightService
{
    public function snapshot(Patient $patient): array
    {
        $patient->loadMissing(['unit']);

        $now = now(config('app.timezone'));

        $recentAppointments = $patient->appointments()
            ->with(['unit', 'procedure', 'professional.user'])
            ->orderByDesc('scheduled_start')
            ->limit(6)
            ->get();

        $upcomingAppointments = $patient->appointments()
            ->with(['unit', 'procedure', 'professional.user'])
            ->where('scheduled_start', '>=', $now)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->orderBy('scheduled_start')
            ->limit(3)
            ->get();

        $openReceivables = $patient->accountsReceivable()
            ->with(['installments', 'unit'])
            ->whereIn('status', ['open', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->get();

        $treatmentPlans = $patient->treatmentPlans()
            ->withCount('items')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $documentAcceptances = $patient->documentAcceptances()
            ->with('documentTemplate')
            ->orderByDesc('accepted_at')
            ->limit(5)
            ->get();

        $acceptedTemplateIds = $patient->documentAcceptances()
            ->pluck('document_template_id');

        $pendingDocumentsCount = DocumentTemplate::query()
            ->where('is_active', true)
            ->where(function ($query) use ($patient) {
                $query->whereNull('unit_id');

                if ($patient->unit_id) {
                    $query->orWhere('unit_id', $patient->unit_id);
                }
            })
            ->when($acceptedTemplateIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $acceptedTemplateIds))
            ->count();

        $openBalance = (float) $openReceivables->sum('net_amount');
        $overdueBalance = (float) $openReceivables
            ->filter(fn ($receivable) => optional($receivable->due_date)?->isPast())
            ->sum('net_amount');

        $openInstallments = $openReceivables
            ->flatMap(fn ($receivable) => $receivable->installments)
            ->whereIn('status', ['open', 'overdue'])
            ->sortBy('due_date')
            ->values();

        $nextInstallment = $openInstallments->first();
        $noShowCount = $patient->appointments()
            ->where('status', 'no_show')
            ->where('scheduled_start', '>=', $now->copy()->subDays(180))
            ->count();

        $attention = $this->attentionLevel(
            overdueBalance: $overdueBalance,
            noShowCount: $noShowCount,
            pendingDocumentsCount: $pendingDocumentsCount,
            lastVisitAt: $patient->last_visit_at,
            hasUpcomingAppointment: $upcomingAppointments->isNotEmpty(),
        );

        return [
            'identity' => [
                'name' => $patient->name,
                'preferred_name' => $patient->preferred_name,
                'cpf' => $patient->cpf,
                'birth_date' => $patient->birth_date?->format('d/m/Y'),
                'age' => $patient->birth_date?->age,
                'phone' => $patient->phone,
                'whatsapp' => $patient->whatsapp,
                'email' => $patient->email,
                'occupation' => $patient->occupation,
                'unit_name' => $patient->unit?->name,
                'last_visit_at' => $patient->last_visit_at,
            ],
            'attention' => $attention,
            'summary' => [
                'open_balance' => $openBalance,
                'overdue_balance' => $overdueBalance,
                'no_show_count' => $noShowCount,
                'pending_documents_count' => $pendingDocumentsCount,
                'active_treatment_plans' => $treatmentPlans
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
                'next_installment' => $nextInstallment,
                'next_appointment' => $upcomingAppointments->first(),
                'last_appointment' => $recentAppointments->first(),
            ],
            'recent_appointments' => $recentAppointments,
            'upcoming_appointments' => $upcomingAppointments,
            'open_receivables' => $openReceivables,
            'open_installments' => $openInstallments->take(5),
            'treatment_plans' => $treatmentPlans,
            'document_acceptances' => $documentAcceptances,
        ];
    }

    private function attentionLevel(
        float $overdueBalance,
        int $noShowCount,
        int $pendingDocumentsCount,
        mixed $lastVisitAt,
        bool $hasUpcomingAppointment,
    ): array {
        $reasons = collect();

        if ($overdueBalance > 0) {
            $reasons->push('Financeiro com valores vencidos aguardando ação.');
        }

        if ($noShowCount >= 2) {
            $reasons->push('Paciente com reincidência de faltas nos últimos 180 dias.');
        }

        if ($pendingDocumentsCount > 0) {
            $reasons->push('Existem documentos ainda não aceitos no prontuário digital.');
        }

        if ($lastVisitAt && $lastVisitAt->lte(now(config('app.timezone'))->copy()->subDays(120)) && ! $hasUpcomingAppointment) {
            $reasons->push('Paciente em janela forte de reativação sem agenda futura.');
        }

        $level = match (true) {
            $reasons->count() >= 3 => 'critico',
            $reasons->isNotEmpty() => 'alerta',
            default => 'estavel',
        };

        $label = match ($level) {
            'critico' => 'Crítico',
            'alerta' => 'Alerta',
            default => 'Estável',
        };

        return [
            'level' => $level,
            'label' => $label,
            'reasons' => $reasons->values()->all(),
        ];
    }
}
