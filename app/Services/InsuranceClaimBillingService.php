<?php

namespace App\Services;

use App\Models\InsuranceAuthorizationItem;
use App\Models\InsuranceClaimBatch;
use App\Models\InsuranceClaimGuide;
use App\Models\InsuranceClaimItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InsuranceClaimBillingService
{
    public function batchStatusOptions(): array
    {
        return [
            'draft' => 'Rascunho',
            'submitted' => 'Enviado',
            'partial_gloss' => 'Glosa parcial',
            'glossed' => 'Glosado',
            'paid' => 'Pago',
            'cancelled' => 'Cancelado',
        ];
    }

    public function claimItemStatusOptions(): array
    {
        return [
            'draft' => 'Rascunho',
            'submitted' => 'Enviado',
            'paid' => 'Pago',
            'partial_gloss' => 'Glosa parcial',
            'glossed' => 'Glosado',
            'represented' => 'Reapresentado',
            'cancelled' => 'Cancelado',
        ];
    }

    public function summary(?int $unitId = null): array
    {
        $baseQuery = $this->scopeBatches(InsuranceClaimBatch::query(), $unitId);
        $itemsQuery = $this->scopeClaimItems(InsuranceClaimItem::query(), $unitId);

        return [
            'pending_billing_count' => $this->pendingExecutionGroups($unitId)->sum('eligible_items_count'),
            'draft_batches_count' => (clone $baseQuery)->where('status', 'draft')->count(),
            'submitted_batches_count' => (clone $baseQuery)->where('status', 'submitted')->count(),
            'glossed_items_count' => (clone $itemsQuery)->whereIn('status', ['partial_gloss', 'glossed'])->count(),
            'representation_candidates_count' => $this->representationCandidateItems($unitId)->count(),
            'claimed_total' => round((float) (clone $baseQuery)->sum('claimed_total'), 2),
            'received_total' => round((float) (clone $baseQuery)->sum('received_total'), 2),
            'gloss_total' => round((float) (clone $baseQuery)->sum('gloss_total'), 2),
        ];
    }

    public function pendingExecutionGroups(?int $unitId = null, int $limit = 12): Collection
    {
        return $this->billableAuthorizationItems($unitId)
            ->groupBy(function (InsuranceAuthorizationItem $item): string {
                return implode('|', [
                    (string) $item->authorization?->unit_id,
                    (string) $item->authorization?->insurance_plan_id,
                    $this->resolveExecutionMoment($item)->format('Y-m'),
                ]);
            })
            ->map(function (Collection $items): array {
                $first = $items->first();
                $executionWindow = $items
                    ->map(fn (InsuranceAuthorizationItem $item) => $this->resolveExecutionMoment($item))
                    ->sort()
                    ->values();

                return [
                    'unit_id' => $first?->authorization?->unit_id,
                    'unit_name' => $first?->authorization?->unit?->name ?? 'Sem unidade',
                    'insurance_plan_id' => $first?->authorization?->insurance_plan_id,
                    'insurance_plan_name' => $first?->authorization?->insurancePlan?->name ?? 'Convenio',
                    'competence_month' => $this->resolveExecutionMoment($first)->format('Y-m'),
                    'eligible_items_count' => $items->count(),
                    'patient_count' => $items->pluck('authorization.patient_id')->filter()->unique()->count(),
                    'claimed_total' => round((float) $items->sum('authorized_amount'), 2),
                    'first_execution_at' => $executionWindow->first()?->toIso8601String(),
                    'last_execution_at' => $executionWindow->last()?->toIso8601String(),
                ];
            })
            ->sortBy([
                ['competence_month', 'asc'],
                ['insurance_plan_name', 'asc'],
            ])
            ->take($limit)
            ->values();
    }

    public function recentBatches(?int $unitId = null, ?string $status = null, int $limit = 15): Collection
    {
        return $this->scopeBatches(
            InsuranceClaimBatch::query()->with([
                'unit',
                'insurancePlan',
                'createdBy',
                'submittedBy',
                'guides.patient',
                'guides.items',
            ]),
            $unitId,
        )
            ->when(filled($status), fn (Builder $query): Builder => $query->where('status', $status))
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function createDraftBatch(int $insurancePlanId, string $competenceMonth, ?int $unitId = null, ?int $createdByUserId = null): InsuranceClaimBatch
    {
        $candidates = $this->billableAuthorizationItems($unitId, $insurancePlanId)
            ->filter(fn (InsuranceAuthorizationItem $item): bool => $this->resolveExecutionMoment($item)->format('Y-m') === $competenceMonth)
            ->values();

        if ($candidates->isEmpty()) {
            throw new RuntimeException('Nao existem itens elegiveis para faturar neste convenio e competencia.');
        }

        $unitId ??= (int) $candidates->first()->authorization?->unit_id;

        return $this->buildBatchFromItems(
            items: $candidates,
            unitId: $unitId,
            insurancePlanId: $insurancePlanId,
            competenceMonth: $competenceMonth,
            batchType: 'initial',
            createdByUserId: $createdByUserId,
        );
    }

    public function createDraftBatchesForCompetence(string $competenceMonth, ?int $unitId = null, ?int $insurancePlanId = null, ?int $createdByUserId = null): int
    {
        $groups = $this->pendingExecutionGroups($unitId)
            ->filter(function (array $group) use ($competenceMonth, $insurancePlanId): bool {
                if ($group['competence_month'] !== $competenceMonth) {
                    return false;
                }

                if ($insurancePlanId !== null && (int) $group['insurance_plan_id'] !== $insurancePlanId) {
                    return false;
                }

                return true;
            });

        $count = 0;

        foreach ($groups as $group) {
            $this->createDraftBatch(
                insurancePlanId: (int) $group['insurance_plan_id'],
                competenceMonth: (string) $group['competence_month'],
                unitId: (int) $group['unit_id'],
                createdByUserId: $createdByUserId,
            );
            $count++;
        }

        return $count;
    }

    public function submitBatch(InsuranceClaimBatch $batch, array $payload = []): InsuranceClaimBatch
    {
        if ($batch->status !== 'draft') {
            throw new RuntimeException('Somente lotes em rascunho podem ser enviados.');
        }

        $batch->loadMissing('guides.items');

        DB::transaction(function () use ($batch, $payload): void {
            $batch->update([
                'status' => 'submitted',
                'submitted_by_user_id' => $payload['submitted_by_user_id'] ?? auth()->id(),
                'submitted_at' => now(config('app.timezone')),
                'batch_number' => $payload['batch_number'] ?? $this->generateBatchNumber($batch),
                'last_status_message' => $payload['message'] ?? 'Lote enviado para faturamento de convenio.',
            ]);

            foreach ($batch->guides as $guide) {
                $guide->update([
                    'status' => 'submitted',
                    'external_guide_number' => $guide->external_guide_number ?? $this->generateGuideNumber($guide),
                ]);

                $guide->items()->update([
                    'status' => 'submitted',
                ]);
            }
        });

        return $batch->refresh()->load([
            'unit',
            'insurancePlan',
            'createdBy',
            'submittedBy',
            'guides.patient',
            'guides.items.procedure',
        ]);
    }

    public function registerBatchReturn(InsuranceClaimBatch $batch, array $itemPayloads, array $payload = []): InsuranceClaimBatch
    {
        $batch->loadMissing('guides.items');

        if (! in_array($batch->status, ['submitted', 'partial_gloss'], true)) {
            throw new RuntimeException('Somente lotes enviados ou parcialmente glosados podem receber retorno.');
        }

        DB::transaction(function () use ($batch, $itemPayloads, $payload): void {
            foreach ($batch->guides as $guide) {
                foreach ($guide->items as $item) {
                    $response = collect($itemPayloads)->firstWhere('id', $item->id);

                    if ($response === null) {
                        continue;
                    }

                    $claimedAmount = round((float) $item->claimed_amount, 2);
                    $claimedQuantity = round((float) $item->claimed_quantity, 2);
                    $approvedAmount = round((float) ($response['approved_amount'] ?? $claimedAmount), 2);
                    $approvedQuantity = round((float) ($response['approved_quantity'] ?? $claimedQuantity), 2);
                    $receivedAmount = round((float) ($response['received_amount'] ?? $approvedAmount), 2);
                    $glossAmount = round(max(0, $claimedAmount - $receivedAmount), 2);

                    $status = match (true) {
                        $receivedAmount <= 0 => 'glossed',
                        $receivedAmount < $claimedAmount => 'partial_gloss',
                        default => 'paid',
                    };

                    $item->update([
                        'status' => $status,
                        'approved_quantity' => $approvedQuantity,
                        'claimed_amount' => $claimedAmount,
                        'approved_amount' => $approvedAmount,
                        'received_amount' => $receivedAmount,
                        'gloss_amount' => $glossAmount,
                        'gloss_reason' => $response['gloss_reason'] ?? null,
                        'meta' => array_merge($item->meta ?? [], [
                            'last_return_at' => now(config('app.timezone'))->toDateTimeString(),
                        ]),
                    ]);
                }

                $guide->refresh()->load('items');

                $guideReceived = round((float) $guide->items->sum('received_amount'), 2);
                $guideApproved = round((float) $guide->items->sum('approved_amount'), 2);
                $guideGloss = round((float) $guide->items->sum('gloss_amount'), 2);
                $guideStatus = match (true) {
                    $guideReceived <= 0 && $guideGloss > 0 => 'glossed',
                    $guideGloss > 0 => 'partial_gloss',
                    default => 'paid',
                };

                $guide->update([
                    'status' => $guideStatus,
                    'approved_total' => $guideApproved,
                    'received_total' => $guideReceived,
                    'gloss_total' => $guideGloss,
                ]);
            }

            $batch->refresh()->load('guides.items');

            $receivedTotal = round((float) $batch->guides->sum('received_total'), 2);
            $approvedTotal = round((float) $batch->guides->sum('approved_total'), 2);
            $glossTotal = round((float) $batch->guides->sum('gloss_total'), 2);
            $batchStatus = match (true) {
                $receivedTotal <= 0 && $glossTotal > 0 => 'glossed',
                $glossTotal > 0 => 'partial_gloss',
                default => 'paid',
            };

            $batch->update([
                'status' => $batchStatus,
                'approved_total' => $approvedTotal,
                'received_total' => $receivedTotal,
                'gloss_total' => $glossTotal,
                'processed_at' => now(config('app.timezone')),
                'paid_at' => $batchStatus === 'paid' ? ($payload['paid_at'] ?? now(config('app.timezone'))) : null,
                'last_status_message' => $payload['message']
                    ?? match ($batchStatus) {
                        'paid' => 'Lote pago integralmente pela operadora.',
                        'glossed' => 'Lote retornou glosado integralmente.',
                        default => 'Lote retornou com glosa parcial.',
                    },
                'response_payload' => array_merge($batch->response_payload ?? [], [
                    'received_at' => now(config('app.timezone'))->toDateTimeString(),
                    'items' => collect($itemPayloads)->values()->all(),
                ]),
            ]);
        });

        return $batch->refresh()->load([
            'unit',
            'insurancePlan',
            'createdBy',
            'submittedBy',
            'guides.patient',
            'guides.items.procedure',
        ]);
    }

    public function createRepresentationBatch(InsuranceClaimBatch $sourceBatch, ?string $competenceMonth = null, ?int $createdByUserId = null): InsuranceClaimBatch
    {
        $sourceBatch->loadMissing('guides.items.authorizationItem.authorization.insurancePlan', 'guides.items.treatmentPlanItem', 'guides.items.representations', 'guides.patient', 'guides.professional');

        $items = $sourceBatch->guides
            ->flatMap(fn (InsuranceClaimGuide $guide) => $guide->items)
            ->filter(function (InsuranceClaimItem $item): bool {
                return in_array($item->status, ['partial_gloss', 'glossed'], true)
                    && (float) $item->gloss_amount > 0
                    && $item->representations->isEmpty();
            })
            ->values();

        if ($items->isEmpty()) {
            throw new RuntimeException('Nao existem itens glosados elegiveis para reapresentacao.');
        }

        $competenceMonth ??= now(config('app.timezone'))->format('Y-m');

        return DB::transaction(function () use ($sourceBatch, $items, $competenceMonth, $createdByUserId) {
            $batch = InsuranceClaimBatch::query()->create([
                'unit_id' => $sourceBatch->unit_id,
                'insurance_plan_id' => $sourceBatch->insurance_plan_id,
                'created_by_user_id' => $createdByUserId ?? auth()->id(),
                'reference' => $this->generateBatchReference($sourceBatch->insurance_plan_id, $sourceBatch->unit_id, 'representation'),
                'batch_type' => 'representation',
                'competence_month' => $competenceMonth,
                'status' => 'draft',
                'guide_count' => 0,
                'claimed_total' => round((float) $items->sum('gloss_amount'), 2),
                'last_status_message' => 'Lote de reapresentacao criado a partir de glosas anteriores.',
                'meta' => [
                    'source_batch_id' => $sourceBatch->id,
                    'source_reference' => $sourceBatch->reference,
                ],
            ]);

            $guidesByAuthorization = $items->groupBy(fn (InsuranceClaimItem $item) => (string) $item->authorizationItem?->authorization?->id);

            foreach ($guidesByAuthorization as $authorizationId => $groupItems) {
                $first = $groupItems->first();
                $authorization = $first?->authorizationItem?->authorization;
                $guide = InsuranceClaimGuide::query()->create([
                    'insurance_claim_batch_id' => $batch->id,
                    'insurance_authorization_id' => $authorization?->id,
                    'patient_id' => $authorization?->patient_id ?? $first?->guide?->patient_id,
                    'professional_id' => $authorization?->professional_id ?? $first?->guide?->professional_id,
                    'treatment_plan_id' => $authorization?->treatment_plan_id ?? $first?->guide?->treatment_plan_id,
                    'reference' => $this->generateGuideReference($batch->id, (int) ($authorization?->id ?? 0)),
                    'guide_type' => 'sp_sadt',
                    'status' => 'draft',
                    'authorization_number' => $authorization?->authorization_number,
                    'external_guide_number' => $authorization?->external_guide_number,
                    'claimed_total' => round((float) $groupItems->sum('gloss_amount'), 2),
                    'executed_at' => $groupItems
                        ->map(fn (InsuranceClaimItem $item) => $item->executed_at ?? $item->guide?->executed_at)
                        ->filter()
                        ->sort()
                        ->first(),
                    'meta' => [
                        'representation' => true,
                    ],
                ]);

                foreach ($groupItems as $sourceItem) {
                    $claimedQuantity = max(0, round((float) $sourceItem->claimed_quantity - (float) $sourceItem->approved_quantity, 2));

                    if ($claimedQuantity <= 0) {
                        $claimedQuantity = round((float) $sourceItem->claimed_quantity, 2);
                    }

                    InsuranceClaimItem::query()->create([
                        'insurance_claim_guide_id' => $guide->id,
                        'insurance_authorization_item_id' => $sourceItem->insurance_authorization_item_id,
                        'treatment_plan_item_id' => $sourceItem->treatment_plan_item_id,
                        'procedure_id' => $sourceItem->procedure_id,
                        'represented_from_claim_item_id' => $sourceItem->id,
                        'description' => $sourceItem->description,
                        'status' => 'draft',
                        'executed_at' => $sourceItem->executed_at,
                        'claimed_quantity' => $claimedQuantity,
                        'approved_quantity' => 0,
                        'claimed_amount' => $sourceItem->gloss_amount,
                        'approved_amount' => 0,
                        'received_amount' => 0,
                        'gloss_amount' => 0,
                        'meta' => [
                            'representation' => true,
                            'source_claim_item_id' => $sourceItem->id,
                        ],
                    ]);

                    $sourceItem->update([
                        'status' => 'represented',
                        'represented_at' => now(config('app.timezone')),
                    ]);
                }

                $guide->update([
                    'claimed_total' => round((float) $guide->items()->sum('claimed_amount'), 2),
                ]);
            }

            $batch->update([
                'guide_count' => $batch->guides()->count(),
                'claimed_total' => round((float) $batch->guides()->sum('claimed_total'), 2),
            ]);

            return $batch->load([
                'unit',
                'insurancePlan',
                'createdBy',
                'guides.patient',
                'guides.items',
            ]);
        });
    }

    public function exportPayload(InsuranceClaimBatch $batch): array
    {
        $batch->loadMissing([
            'unit',
            'insurancePlan',
            'createdBy',
            'submittedBy',
            'guides.authorization',
            'guides.patient',
            'guides.professional.user',
            'guides.treatmentPlan',
            'guides.items.procedure',
            'guides.items.authorizationItem.authorization',
            'guides.items.representedFrom',
        ]);

        return [
            'schema' => 'tiss-billing-ready-internal-v1',
            'generated_at' => now(config('app.timezone'))->toIso8601String(),
            'batch' => [
                'reference' => $batch->reference,
                'batch_number' => $batch->batch_number,
                'batch_type' => $batch->batch_type,
                'competence_month' => $batch->competence_month,
                'status' => $batch->status,
                'tiss_version' => $batch->tiss_version,
                'guide_count' => $batch->guide_count,
                'claimed_total' => round((float) $batch->claimed_total, 2),
                'approved_total' => round((float) $batch->approved_total, 2),
                'received_total' => round((float) $batch->received_total, 2),
                'gloss_total' => round((float) $batch->gloss_total, 2),
                'submitted_at' => $batch->submitted_at?->toIso8601String(),
                'processed_at' => $batch->processed_at?->toIso8601String(),
                'paid_at' => $batch->paid_at?->toIso8601String(),
                'last_status_message' => $batch->last_status_message,
            ],
            'insurance_plan' => [
                'name' => $batch->insurancePlan?->name,
                'code' => $batch->insurancePlan?->code,
                'ans_registration' => $batch->insurancePlan?->ans_registration,
                'operator_document' => $batch->insurancePlan?->operator_document,
                'tiss_table_code' => $batch->insurancePlan?->tiss_table_code,
            ],
            'provider_unit' => [
                'name' => $batch->unit?->name,
                'document' => $batch->unit?->document,
                'city' => $batch->unit?->city,
                'state' => $batch->unit?->state,
            ],
            'guides' => $batch->guides->map(function (InsuranceClaimGuide $guide): array {
                return [
                    'reference' => $guide->reference,
                    'guide_type' => $guide->guide_type,
                    'status' => $guide->status,
                    'authorization_number' => $guide->authorization_number,
                    'external_guide_number' => $guide->external_guide_number,
                    'claimed_total' => round((float) $guide->claimed_total, 2),
                    'approved_total' => round((float) $guide->approved_total, 2),
                    'received_total' => round((float) $guide->received_total, 2),
                    'gloss_total' => round((float) $guide->gloss_total, 2),
                    'executed_at' => $guide->executed_at?->toIso8601String(),
                    'patient' => [
                        'name' => $guide->patient?->name,
                        'cpf' => $guide->patient?->cpf,
                    ],
                    'professional' => $guide->professional?->user?->name,
                    'plan' => [
                        'id' => $guide->treatment_plan_id,
                        'code' => $guide->treatmentPlan?->code,
                        'name' => $guide->treatmentPlan?->name,
                    ],
                    'items' => $guide->items->map(function (InsuranceClaimItem $item): array {
                        return [
                            'id' => $item->id,
                            'status' => $item->status,
                            'description' => $item->description,
                            'procedure_name' => $item->procedure?->name,
                            'procedure_code' => $item->procedure?->code,
                            'executed_at' => $item->executed_at?->toIso8601String(),
                            'claimed_quantity' => round((float) $item->claimed_quantity, 2),
                            'approved_quantity' => round((float) $item->approved_quantity, 2),
                            'claimed_amount' => round((float) $item->claimed_amount, 2),
                            'approved_amount' => round((float) $item->approved_amount, 2),
                            'received_amount' => round((float) $item->received_amount, 2),
                            'gloss_amount' => round((float) $item->gloss_amount, 2),
                            'gloss_reason' => $item->gloss_reason,
                            'authorization_reference' => $item->authorizationItem?->authorization?->reference,
                            'represented_from_claim_item_id' => $item->represented_from_claim_item_id,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    public function representationCandidateItems(?int $unitId = null): Collection
    {
        return $this->scopeClaimItems(
            InsuranceClaimItem::query()->with('representations', 'guide.batch.insurancePlan', 'guide.patient'),
            $unitId,
        )
            ->whereIn('status', ['partial_gloss', 'glossed'])
            ->where('gloss_amount', '>', 0)
            ->get()
            ->filter(fn (InsuranceClaimItem $item): bool => $item->representations->isEmpty())
            ->values();
    }

    private function buildBatchFromItems(Collection $items, int $unitId, int $insurancePlanId, string $competenceMonth, string $batchType, ?int $createdByUserId): InsuranceClaimBatch
    {
        return DB::transaction(function () use ($items, $unitId, $insurancePlanId, $competenceMonth, $batchType, $createdByUserId) {
            $batch = InsuranceClaimBatch::query()->create([
                'unit_id' => $unitId,
                'insurance_plan_id' => $insurancePlanId,
                'created_by_user_id' => $createdByUserId ?? auth()->id(),
                'reference' => $this->generateBatchReference($insurancePlanId, $unitId, $batchType),
                'batch_type' => $batchType,
                'competence_month' => $competenceMonth,
                'status' => 'draft',
                'guide_count' => 0,
                'claimed_total' => round((float) $items->sum('authorized_amount'), 2),
                'approved_total' => 0,
                'received_total' => 0,
                'gloss_total' => 0,
                'meta' => [
                    'generated_item_ids' => $items->pluck('id')->all(),
                ],
            ]);

            $groups = $items->groupBy(fn (InsuranceAuthorizationItem $item) => (string) $item->authorization_id);

            foreach ($groups as $authorizationId => $groupItems) {
                $authorization = $groupItems->first()?->authorization;
                $guide = InsuranceClaimGuide::query()->create([
                    'insurance_claim_batch_id' => $batch->id,
                    'insurance_authorization_id' => $authorization?->id,
                    'patient_id' => $authorization?->patient_id,
                    'professional_id' => $authorization?->professional_id,
                    'treatment_plan_id' => $authorization?->treatment_plan_id,
                    'reference' => $this->generateGuideReference($batch->id, (int) $authorizationId),
                    'guide_type' => 'sp_sadt',
                    'status' => 'draft',
                    'authorization_number' => $authorization?->authorization_number,
                    'external_guide_number' => $authorization?->external_guide_number,
                    'claimed_total' => round((float) $groupItems->sum('authorized_amount'), 2),
                    'executed_at' => $groupItems
                        ->map(fn (InsuranceAuthorizationItem $item) => $this->resolveExecutionMoment($item))
                        ->sort()
                        ->first(),
                ]);

                foreach ($groupItems as $item) {
                    InsuranceClaimItem::query()->create([
                        'insurance_claim_guide_id' => $guide->id,
                        'insurance_authorization_item_id' => $item->id,
                        'treatment_plan_item_id' => $item->treatment_plan_item_id,
                        'procedure_id' => $item->procedure_id,
                        'description' => $item->description,
                        'status' => 'draft',
                        'executed_at' => $this->resolveExecutionMoment($item),
                        'claimed_quantity' => $item->authorized_quantity > 0 ? $item->authorized_quantity : $item->requested_quantity,
                        'approved_quantity' => 0,
                        'claimed_amount' => $item->authorized_amount > 0 ? $item->authorized_amount : $item->requested_amount,
                        'approved_amount' => 0,
                        'received_amount' => 0,
                        'gloss_amount' => 0,
                    ]);
                }
            }

            $batch->update([
                'guide_count' => $batch->guides()->count(),
                'claimed_total' => round((float) $batch->guides()->sum('claimed_total'), 2),
            ]);

            return $batch->load([
                'unit',
                'insurancePlan',
                'createdBy',
                'guides.patient',
                'guides.items.procedure',
            ]);
        });
    }

    private function billableAuthorizationItems(?int $unitId = null, ?int $insurancePlanId = null): Collection
    {
        return InsuranceAuthorizationItem::query()
            ->with([
                'authorization.unit',
                'authorization.patient',
                'authorization.insurancePlan',
                'authorization.professional.user',
                'authorization.treatmentPlan',
                'treatmentPlanItem',
                'procedure',
                'claimItems',
            ])
            ->whereIn('status', ['authorized', 'partial'])
            ->whereHas('authorization', function (Builder $query) use ($unitId, $insurancePlanId): void {
                $query->whereIn('status', ['authorized', 'partially_authorized']);

                if ($unitId !== null) {
                    $query->where('unit_id', $unitId);
                }

                if ($insurancePlanId !== null) {
                    $query->where('insurance_plan_id', $insurancePlanId);
                }
            })
            ->get()
            ->filter(function (InsuranceAuthorizationItem $item): bool {
                $executionMoment = $this->resolveExecutionMoment($item, false);

                if ($executionMoment === null) {
                    return false;
                }

                return $item->claimItems->isEmpty();
            })
            ->values();
    }

    private function resolveExecutionMoment(InsuranceAuthorizationItem $item, bool $fallbackToNow = true): ?Carbon
    {
        $moment = $item->executed_at
            ?? $item->treatmentPlanItem?->completed_at
            ?? (($item->treatmentPlanItem?->status === 'done') ? $item->treatmentPlanItem?->updated_at : null);

        if ($moment === null) {
            return $fallbackToNow ? now(config('app.timezone')) : null;
        }

        return $moment instanceof Carbon
            ? $moment
            : Carbon::parse((string) $moment, config('app.timezone'));
    }

    private function scopeBatches(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    private function scopeClaimItems(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->whereHas('guide.batch', fn (Builder $builder): Builder => $builder->where('unit_id', $unitId));
        }

        return $query;
    }

    private function generateBatchReference(int $insurancePlanId, int $unitId, string $batchType): string
    {
        return strtoupper('LOTE-'.$batchType.'-'.now(config('app.timezone'))->format('YmdHis')."-{$unitId}-{$insurancePlanId}");
    }

    private function generateBatchNumber(InsuranceClaimBatch $batch): string
    {
        return now(config('app.timezone'))->format('ymd').str_pad((string) $batch->id, 7, '0', STR_PAD_LEFT);
    }

    private function generateGuideReference(int $batchId, int $authorizationId): string
    {
        return 'GUIA-FAT-'.$batchId.'-'.$authorizationId.'-'.now(config('app.timezone'))->format('His');
    }

    private function generateGuideNumber(InsuranceClaimGuide $guide): string
    {
        return 'G'.now(config('app.timezone'))->format('ymd').str_pad((string) $guide->id, 6, '0', STR_PAD_LEFT);
    }
}
