<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ClinicalGovernanceService
{
    public function snapshot(?int $unitId = null, int $limit = 5): array
    {
        $unitId = $this->resolveScopeUnitId($unitId);

        return [
            'scope' => [
                'unit_id' => $unitId,
                'label' => $this->resolveScopeLabel($unitId),
            ],
            'stats' => $this->stats($unitId),
            'alerts' => $this->alerts($unitId, $limit),
        ];
    }

    public function stats(?int $unitId = null): array
    {
        return [
            'missing_clinical_records_count' => $this->completedAppointmentsWithoutRecord($unitId)->count(),
            'plans_without_followup_count' => $this->approvedPlansWithoutFollowUp($unitId)->count(),
            'pending_documents_count' => $this->patientsWithPendingRequiredDocuments($unitId)->count(),
            'overdue_plan_items_count' => $this->overdueTreatmentPlanItems($unitId)->count(),
        ];
    }

    public function alerts(?int $unitId = null, int $limit = 5): array
    {
        return [
            'completed_without_record' => $this->completedAppointmentsWithoutRecord($unitId, $limit),
            'plans_without_followup' => $this->approvedPlansWithoutFollowUp($unitId, $limit),
            'pending_required_documents' => $this->patientsWithPendingRequiredDocuments($unitId, $limit),
            'overdue_treatment_items' => $this->overdueTreatmentPlanItems($unitId, $limit),
        ];
    }

    public function completedAppointmentsWithoutRecord(?int $unitId = null, ?int $limit = null): Collection
    {
        $query = $this->scopeQuery(
            Appointment::query()->with(['patient', 'professional.user', 'unit']),
            $this->resolveScopeUnitId($unitId),
        )
            ->where('status', 'completed')
            ->whereDoesntHave('clinicalRecords')
            ->whereBetween('scheduled_start', [
                now(config('app.timezone'))->copy()->subDays(30)->startOfDay(),
                now(config('app.timezone'))->copy()->endOfDay(),
            ])
            ->orderByDesc('scheduled_start');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function approvedPlansWithoutFollowUp(?int $unitId = null, ?int $limit = null): Collection
    {
        $query = $this->scopeQuery(
            TreatmentPlan::query()->with(['patient', 'professional.user', 'unit', 'items']),
            $this->resolveScopeUnitId($unitId),
        )
            ->where('status', 'approved')
            ->whereHas('items', function (Builder $query): void {
                $query
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->whereNull('completed_at');
            })
            ->whereDoesntHave('patient.appointments', function (Builder $query): void {
                $query
                    ->where('scheduled_start', '>=', now(config('app.timezone')))
                    ->whereNotIn('status', ['cancelled', 'no_show']);
            })
            ->orderByDesc('updated_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function patientsWithPendingRequiredDocuments(?int $unitId = null, ?int $limit = null): Collection
    {
        $unitId = $this->resolveScopeUnitId($unitId);

        $requiredTemplateCount = $this->scopeDocumentTemplates(DocumentTemplate::query(), $unitId)
            ->where('is_active', true)
            ->count();

        if ($requiredTemplateCount === 0) {
            return collect();
        }

        $patients = $this->scopePatients(
            Patient::query()->with(['unit', 'latestAppointment', 'documentAcceptances']),
            $unitId,
        )
            ->whereHas('treatmentPlans', function (Builder $query): void {
                $query->where('status', 'approved');
            })
            ->withCount('documentAcceptances')
            ->get()
            ->filter(fn (Patient $patient): bool => (int) $patient->document_acceptances_count < $requiredTemplateCount)
            ->sortBy('document_acceptances_count')
            ->values();

        if ($limit !== null) {
            $patients = $patients->take($limit)->values();
        }

        return $patients->map(function (Patient $patient) use ($requiredTemplateCount) {
            $patient->setAttribute('required_documents_count', $requiredTemplateCount);
            $patient->setAttribute('pending_documents_count', max(0, $requiredTemplateCount - (int) $patient->document_acceptances_count));

            return $patient;
        });
    }

    public function overdueTreatmentPlanItems(?int $unitId = null, ?int $limit = null): Collection
    {
        $query = TreatmentPlanItem::query()
            ->with(['treatmentPlan.patient', 'treatmentPlan.professional.user', 'treatmentPlan.unit', 'procedure']);

        if ($resolvedUnitId = $this->resolveScopeUnitId($unitId)) {
            $query->whereHas('treatmentPlan', fn (Builder $builder) => $builder->where('unit_id', $resolvedUnitId));
        }

        $query
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNull('completed_at')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<', now(config('app.timezone')))
            ->orderBy('scheduled_for');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    private function resolveScopeUnitId(?int $unitId = null): ?int
    {
        if ($unitId !== null) {
            return $unitId;
        }

        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
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

    private function scopeQuery(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    private function scopePatients(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    private function scopeDocumentTemplates(Builder $query, ?int $unitId): Builder
    {
        if ($unitId === null) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($unitId): void {
            $builder->whereNull('unit_id')
                ->orWhere('unit_id', $unitId);
        });
    }
}
