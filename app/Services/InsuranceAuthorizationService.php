<?php

namespace App\Services;

use App\Models\InsuranceAuthorization;
use App\Models\InsuranceAuthorizationItem;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InsuranceAuthorizationService
{
    public function statusOptions(): array
    {
        return [
            'draft' => 'Rascunho',
            'submitted' => 'Enviado',
            'authorized' => 'Autorizado',
            'partially_authorized' => 'Parcial',
            'denied' => 'Negado',
            'expired' => 'Expirado',
            'cancelled' => 'Cancelado',
        ];
    }

    public function submissionChannelOptions(): array
    {
        return [
            'manual' => 'Manual',
            'email' => 'E-mail',
            'portal' => 'Portal da operadora',
            'api' => 'API',
            'tiss' => 'TISS',
        ];
    }

    public function summary(?int $unitId = null): array
    {
        $now = now(config('app.timezone'));
        $baseQuery = $this->scopeAuthorizations(InsuranceAuthorization::query(), $unitId);

        return [
            'draft_count' => (clone $baseQuery)->where('status', 'draft')->count(),
            'submitted_count' => (clone $baseQuery)->where('status', 'submitted')->count(),
            'expiring_count' => (clone $baseQuery)
                ->whereIn('status', ['authorized', 'partially_authorized'])
                ->whereNotNull('valid_until')
                ->whereBetween('valid_until', [$now, $now->copy()->addDays(7)])
                ->count(),
            'authorized_to_schedule_count' => $this->scopeAuthorizationItems(InsuranceAuthorizationItem::query(), $unitId)
                ->whereIn('status', ['authorized', 'partial'])
                ->whereNull('executed_at')
                ->where(function (Builder $query) use ($now): void {
                    $query->whereNull('valid_until')
                        ->orWhere('valid_until', '>=', $now);
                })
                ->count(),
            'denied_items_count' => $this->scopeAuthorizationItems(InsuranceAuthorizationItem::query(), $unitId)
                ->where('status', 'denied')
                ->count(),
            'requested_total' => round((float) (clone $baseQuery)
                ->whereIn('status', ['draft', 'submitted'])
                ->sum('requested_total'), 2),
            'authorized_total' => round((float) (clone $baseQuery)
                ->whereIn('status', ['authorized', 'partially_authorized'])
                ->sum('authorized_total'), 2),
        ];
    }

    public function candidateTreatmentPlans(?int $unitId = null, int $limit = 12): Collection
    {
        return $this->scopeTreatmentPlans(
            TreatmentPlan::query()->with([
                'patient',
                'unit',
                'insurancePlan',
                'professional.user',
                'items.procedure',
                'items.insuranceAuthorizationItems.authorization',
            ]),
            $unitId,
        )
            ->whereNotNull('insurance_plan_id')
            ->whereIn('status', ['approved', 'partial'])
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->get()
            ->filter(function (TreatmentPlan $plan): bool {
                return $this->planRequiresAuthorization($plan) && $this->eligibleItems($plan)->isNotEmpty();
            })
            ->take($limit)
            ->map(function (TreatmentPlan $plan): array {
                $eligibleItems = $this->eligibleItems($plan);

                return [
                    'treatment_plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'plan_code' => $plan->code,
                    'patient_name' => $plan->patient?->name ?? 'Paciente',
                    'unit_name' => $plan->unit?->name ?? 'Sem unidade',
                    'insurance_plan_name' => $plan->insurancePlan?->name ?? 'Convenio',
                    'professional_name' => $plan->professional?->user?->name,
                    'eligible_items_count' => $eligibleItems->count(),
                    'requested_total' => round((float) $eligibleItems->sum('total_price'), 2),
                    'needs_authorization_by' => $plan->insurancePlan?->requires_authorization ? 'convenio' : 'procedimento',
                ];
            })
            ->values();
    }

    public function recentAuthorizations(?int $unitId = null, ?string $status = null, int $limit = 15): Collection
    {
        return $this->scopeAuthorizations(
            InsuranceAuthorization::query()->with([
                'unit',
                'patient',
                'insurancePlan',
                'treatmentPlan',
                'professional.user',
                'createdBy',
                'items.treatmentPlanItem',
            ]),
            $unitId,
        )
            ->when(filled($status), fn (Builder $query): Builder => $query->where('status', $status))
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function createDraft(TreatmentPlan $plan, array $payload = []): InsuranceAuthorization
    {
        $plan->loadMissing([
            'patient',
            'unit',
            'insurancePlan',
            'professional.user',
            'items.procedure',
            'items.insuranceAuthorizationItems.authorization',
        ]);

        if ($plan->insurance_plan_id === null || ! $plan->insurancePlan) {
            throw new RuntimeException('O plano de tratamento nao possui convenio vinculado.');
        }

        if (! in_array($plan->status, ['approved', 'partial'], true)) {
            throw new RuntimeException('Somente planos aprovados ou parciais podem gerar guia de convenio.');
        }

        if (! $this->planRequiresAuthorization($plan)) {
            throw new RuntimeException('Este plano nao exige autorizacao de convenio.');
        }

        $eligibleItems = $this->eligibleItems($plan);

        if ($eligibleItems->isEmpty()) {
            throw new RuntimeException('Nao existem itens elegiveis para montar uma nova guia.');
        }

        return DB::transaction(function () use ($plan, $eligibleItems, $payload) {
            $authorization = InsuranceAuthorization::query()->create([
                'unit_id' => $plan->unit_id,
                'insurance_plan_id' => $plan->insurance_plan_id,
                'patient_id' => $plan->patient_id,
                'treatment_plan_id' => $plan->id,
                'professional_id' => $plan->professional_id,
                'created_by_user_id' => $payload['created_by_user_id'] ?? auth()->id(),
                'reference' => $this->generateReference($plan->id),
                'status' => 'draft',
                'submission_channel' => $payload['submission_channel']
                    ?? $plan->insurancePlan?->submission_channel
                    ?? 'manual',
                'requested_total' => round((float) $eligibleItems->sum('total_price'), 2),
                'authorized_total' => 0,
                'response_due_at' => now(config('app.timezone'))->addDays((int) ($payload['response_due_days'] ?? 3)),
                'meta' => [
                    'source' => 'treatment_plan',
                    'generated_item_ids' => $eligibleItems->pluck('id')->all(),
                ],
            ]);

            foreach ($eligibleItems as $item) {
                InsuranceAuthorizationItem::query()->create([
                    'insurance_authorization_id' => $authorization->id,
                    'treatment_plan_item_id' => $item->id,
                    'procedure_id' => $item->procedure_id,
                    'description' => $item->description,
                    'tooth_code' => $item->tooth_code,
                    'face' => $item->face,
                    'status' => 'pending',
                    'requested_quantity' => $item->quantity,
                    'authorized_quantity' => 0,
                    'requested_amount' => $item->total_price,
                    'authorized_amount' => 0,
                ]);
            }

            return $authorization->load([
                'unit',
                'patient',
                'insurancePlan',
                'treatmentPlan',
                'professional.user',
                'createdBy',
                'items.treatmentPlanItem',
            ]);
        });
    }

    public function submit(InsuranceAuthorization $authorization, array $payload = []): InsuranceAuthorization
    {
        if ($authorization->status !== 'draft') {
            throw new RuntimeException('Somente guias em rascunho podem ser enviadas.');
        }

        $authorization->update([
            'status' => 'submitted',
            'submitted_at' => now(config('app.timezone')),
            'submission_channel' => $payload['submission_channel'] ?? $authorization->submission_channel,
            'external_guide_number' => $payload['external_guide_number'] ?? $authorization->external_guide_number,
            'response_due_at' => filled($payload['response_due_at'] ?? null)
                ? Carbon::parse((string) $payload['response_due_at'], config('app.timezone'))
                : $authorization->response_due_at,
            'last_status_message' => $payload['message'] ?? 'Guia enviada para a operadora.',
        ]);

        return $authorization->refresh()->load([
            'unit',
            'patient',
            'insurancePlan',
            'treatmentPlan',
            'professional.user',
            'createdBy',
            'items.treatmentPlanItem',
        ]);
    }

    public function registerResponse(InsuranceAuthorization $authorization, array $itemPayloads, array $payload = []): InsuranceAuthorization
    {
        $authorization->loadMissing([
            'insurancePlan',
            'items',
        ]);

        if (in_array($authorization->status, ['cancelled', 'expired'], true)) {
            throw new RuntimeException('A guia nao pode mais receber retorno da operadora.');
        }

        return DB::transaction(function () use ($authorization, $itemPayloads, $payload) {
            $defaultValidUntil = now(config('app.timezone'))
                ->addDays((int) ($payload['valid_days'] ?? $authorization->insurancePlan?->authorization_valid_days ?? 30));

            foreach ($authorization->items as $item) {
                $response = collect($itemPayloads)->firstWhere('id', $item->id);

                if ($response === null) {
                    continue;
                }

                $requestedQuantity = round((float) $item->requested_quantity, 2);
                $requestedAmount = round((float) $item->requested_amount, 2);
                $status = (string) ($response['status'] ?? 'authorized');
                $isDenied = $status === 'denied';
                $authorizedQuantity = $isDenied
                    ? 0
                    : round((float) ($response['authorized_quantity'] ?? $requestedQuantity), 2);
                $authorizedAmount = $isDenied
                    ? 0
                    : round((float) ($response['authorized_amount'] ?? $requestedAmount), 2);

                if (! $isDenied && ($authorizedQuantity < $requestedQuantity || $authorizedAmount < $requestedAmount)) {
                    $status = 'partial';
                } elseif (! $isDenied) {
                    $status = 'authorized';
                }

                $validUntil = null;

                if (! $isDenied) {
                    $validUntil = filled($response['valid_until'] ?? null)
                        ? Carbon::parse((string) $response['valid_until'], config('app.timezone'))
                        : $defaultValidUntil->copy();
                }

                $item->update([
                    'status' => $status,
                    'authorized_quantity' => $authorizedQuantity,
                    'authorized_amount' => $authorizedAmount,
                    'denial_reason' => $response['denial_reason'] ?? null,
                    'valid_until' => $validUntil,
                    'meta' => array_merge($item->meta ?? [], [
                        'last_response_at' => now(config('app.timezone'))->toDateTimeString(),
                    ]),
                ]);
            }

            $authorization->refresh()->load('items');

            $authorizedItems = $authorization->items->whereIn('status', ['authorized', 'partial']);
            $deniedItems = $authorization->items->where('status', 'denied');
            $authorizedTotal = round((float) $authorizedItems->sum('authorized_amount'), 2);
            $validUntil = $authorizedItems
                ->pluck('valid_until')
                ->filter()
                ->map(fn ($date) => $date instanceof CarbonInterface ? $date : Carbon::parse($date, config('app.timezone')))
                ->sort()
                ->first();

            $overallStatus = match (true) {
                $authorizedItems->isNotEmpty() && $deniedItems->isEmpty() => 'authorized',
                $authorizedItems->isNotEmpty() && $deniedItems->isNotEmpty() => 'partially_authorized',
                $authorizedItems->isEmpty() && $deniedItems->count() === $authorization->items->count() => 'denied',
                default => 'submitted',
            };

            $authorization->update([
                'status' => $overallStatus,
                'authorized_total' => $authorizedTotal,
                'authorized_at' => $authorizedItems->isNotEmpty() ? now(config('app.timezone')) : null,
                'authorization_number' => $payload['authorization_number']
                    ?? $authorization->authorization_number
                    ?? ($authorizedItems->isNotEmpty() ? $this->generateAuthorizationNumber($authorization->id) : null),
                'valid_until' => $validUntil,
                'last_status_message' => $payload['message']
                    ?? match ($overallStatus) {
                        'authorized' => 'Guia autorizada integralmente.',
                        'partially_authorized' => 'Guia retornou com autorizacao parcial.',
                        'denied' => 'Guia negada pela operadora.',
                        default => 'Guia atualizada com retorno da operadora.',
                    },
                'response_payload' => array_merge($authorization->response_payload ?? [], [
                    'received_at' => now(config('app.timezone'))->toDateTimeString(),
                    'items' => collect($itemPayloads)->values()->all(),
                ]),
            ]);

            return $authorization->refresh()->load([
                'unit',
                'patient',
                'insurancePlan',
                'treatmentPlan',
                'professional.user',
                'createdBy',
                'items.treatmentPlanItem',
            ]);
        });
    }

    public function markExpired(?int $unitId = null): int
    {
        $now = now(config('app.timezone'));
        $authorizations = $this->scopeAuthorizations(
            InsuranceAuthorization::query()->with('items'),
            $unitId,
        )
            ->whereIn('status', ['authorized', 'partially_authorized'])
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', $now)
            ->get();

        foreach ($authorizations as $authorization) {
            $authorization->items()
                ->whereIn('status', ['authorized', 'partial'])
                ->whereNull('executed_at')
                ->update(['status' => 'expired']);

            $authorization->update([
                'status' => 'expired',
                'last_status_message' => 'Guia expirada automaticamente por validade.',
            ]);
        }

        return $authorizations->count();
    }

    public function exportPayload(InsuranceAuthorization $authorization): array
    {
        $authorization->loadMissing([
            'unit',
            'insurancePlan',
            'patient',
            'treatmentPlan',
            'professional.user',
            'items.procedure',
            'items.treatmentPlanItem',
        ]);

        return [
            'schema' => 'tiss-ready-internal-v1',
            'generated_at' => now(config('app.timezone'))->toIso8601String(),
            'authorization' => [
                'reference' => $authorization->reference,
                'status' => $authorization->status,
                'submission_channel' => $authorization->submission_channel,
                'authorization_number' => $authorization->authorization_number,
                'external_guide_number' => $authorization->external_guide_number,
                'submitted_at' => $authorization->submitted_at?->toIso8601String(),
                'authorized_at' => $authorization->authorized_at?->toIso8601String(),
                'response_due_at' => $authorization->response_due_at?->toIso8601String(),
                'valid_until' => $authorization->valid_until?->toIso8601String(),
                'requested_total' => round((float) $authorization->requested_total, 2),
                'authorized_total' => round((float) $authorization->authorized_total, 2),
                'last_status_message' => $authorization->last_status_message,
            ],
            'insurance_plan' => [
                'name' => $authorization->insurancePlan?->name,
                'code' => $authorization->insurancePlan?->code,
                'ans_registration' => $authorization->insurancePlan?->ans_registration,
                'operator_document' => $authorization->insurancePlan?->operator_document,
                'submission_channel' => $authorization->insurancePlan?->submission_channel,
                'tiss_table_code' => $authorization->insurancePlan?->tiss_table_code,
            ],
            'provider_unit' => [
                'name' => $authorization->unit?->name,
                'document' => $authorization->unit?->document,
                'city' => $authorization->unit?->city,
                'state' => $authorization->unit?->state,
            ],
            'patient' => [
                'name' => $authorization->patient?->name,
                'preferred_name' => $authorization->patient?->preferred_name,
                'birth_date' => $authorization->patient?->birth_date?->toDateString(),
                'cpf' => $authorization->patient?->cpf,
                'email' => $authorization->patient?->email,
                'phone' => $authorization->patient?->phone,
            ],
            'treatment_plan' => [
                'id' => $authorization->treatment_plan_id,
                'code' => $authorization->treatmentPlan?->code,
                'name' => $authorization->treatmentPlan?->name,
                'approved_at' => $authorization->treatmentPlan?->approved_at?->toIso8601String(),
                'professional_name' => $authorization->professional?->user?->name,
            ],
            'items' => $authorization->items
                ->map(function (InsuranceAuthorizationItem $item): array {
                    return [
                        'id' => $item->id,
                        'status' => $item->status,
                        'description' => $item->description,
                        'procedure_name' => $item->procedure?->name,
                        'procedure_code' => $item->procedure?->code,
                        'tooth_code' => $item->tooth_code,
                        'face' => $item->face,
                        'requested_quantity' => round((float) $item->requested_quantity, 2),
                        'authorized_quantity' => round((float) $item->authorized_quantity, 2),
                        'requested_amount' => round((float) $item->requested_amount, 2),
                        'authorized_amount' => round((float) $item->authorized_amount, 2),
                        'denial_reason' => $item->denial_reason,
                        'valid_until' => $item->valid_until?->toIso8601String(),
                        'executed_at' => $item->executed_at?->toIso8601String(),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function eligibleItems(TreatmentPlan $plan): Collection
    {
        return $plan->items
            ->filter(function (TreatmentPlanItem $item) use ($plan): bool {
                if (in_array($item->status, ['done', 'cancelled'], true)) {
                    return false;
                }

                if (! $this->itemRequiresAuthorization($item, $plan)) {
                    return false;
                }

                $activeAuthorization = $item->insuranceAuthorizationItems
                    ->first(function (InsuranceAuthorizationItem $authorizationItem): bool {
                        $authorizationStatus = $authorizationItem->authorization?->status;

                        return in_array((string) $authorizationStatus, ['draft', 'submitted', 'authorized', 'partially_authorized'], true)
                            && ! in_array($authorizationItem->status, ['denied', 'expired'], true)
                            && $authorizationItem->executed_at === null;
                    });

                return $activeAuthorization === null;
            })
            ->values();
    }

    private function planRequiresAuthorization(TreatmentPlan $plan): bool
    {
        if ((bool) $plan->insurancePlan?->requires_authorization) {
            return true;
        }

        return $plan->items->contains(fn (TreatmentPlanItem $item): bool => $this->itemRequiresAuthorization($item, $plan));
    }

    private function itemRequiresAuthorization(TreatmentPlanItem $item, TreatmentPlan $plan): bool
    {
        return (bool) $plan->insurancePlan?->requires_authorization
            || (bool) $item->procedure?->requires_approval;
    }

    private function scopeAuthorizations(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    private function scopeAuthorizationItems(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->whereHas('authorization', fn (Builder $builder): Builder => $builder->where('unit_id', $unitId));
        }

        return $query;
    }

    private function scopeTreatmentPlans(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    private function generateReference(int $planId): string
    {
        return 'GUIA-'.now(config('app.timezone'))->format('YmdHis').'-'.$planId;
    }

    private function generateAuthorizationNumber(int $authorizationId): string
    {
        return 'AUT-'.now(config('app.timezone'))->format('ymd').str_pad((string) $authorizationId, 7, '0', STR_PAD_LEFT);
    }
}
