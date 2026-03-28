<?php

namespace App\Filament\Pages;

use App\Models\InsuranceClaimBatch;
use App\Models\Unit;
use App\Services\InsuranceClaimBillingService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class InsuranceClaimBillingCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Faturamento de convenio';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestao';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Central de faturamento de convenio';

    protected string $view = 'filament.pages.insurance-claim-billing-center';

    public array $filters = [];

    public array $summary = [];

    public array $pendingGroups = [];

    public array $recentBatches = [];

    public array $unitOptions = [];

    public array $statusOptions = [];

    public function mount(InsuranceClaimBillingService $service): void
    {
        $this->unitOptions = Unit::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $this->statusOptions = $service->batchStatusOptions();

        $this->filters = [
            'unit_id' => auth()->user()?->hasRole('superadmin') ? null : auth()->user()?->unit_id,
            'status' => null,
        ];

        $this->loadData($service);
    }

    public function refreshData(InsuranceClaimBillingService $service): void
    {
        $this->validateFilters();
        $this->loadData($service);

        Notification::make()
            ->title('Central de faturamento atualizada.')
            ->success()
            ->send();
    }

    public function createBatch(InsuranceClaimBillingService $service, int $insurancePlanId, string $competenceMonth): void
    {
        try {
            $service->createDraftBatch(
                insurancePlanId: $insurancePlanId,
                competenceMonth: $competenceMonth,
                unitId: ($this->filters['unit_id'] ?? null) ? (int) $this->filters['unit_id'] : null,
                createdByUserId: auth()->id(),
            );

            $this->loadData($service);

            Notification::make()
                ->title('Lote de convenio gerado em rascunho.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function submitBatch(InsuranceClaimBillingService $service, int $batchId): void
    {
        try {
            $service->submitBatch(
                batch: InsuranceClaimBatch::query()->findOrFail($batchId),
                payload: ['submitted_by_user_id' => auth()->id()],
            );

            $this->loadData($service);

            Notification::make()
                ->title('Lote enviado para faturamento do convenio.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function registerPaidReturn(InsuranceClaimBillingService $service, int $batchId): void
    {
        try {
            $batch = InsuranceClaimBatch::query()->with('guides.items')->findOrFail($batchId);

            $payload = $batch->guides
                ->flatMap(fn ($guide) => $guide->items)
                ->map(fn ($item): array => [
                    'id' => $item->id,
                    'approved_quantity' => $item->claimed_quantity,
                    'approved_amount' => $item->claimed_amount,
                    'received_amount' => $item->claimed_amount,
                ])
                ->all();

            $service->registerBatchReturn(
                batch: $batch,
                itemPayloads: $payload,
                payload: ['message' => 'Retorno registrado como pago integralmente.'],
            );

            $this->loadData($service);

            Notification::make()
                ->title('Retorno integral registrado no lote.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function registerPartialGlossReturn(InsuranceClaimBillingService $service, int $batchId): void
    {
        try {
            $batch = InsuranceClaimBatch::query()->with('guides.items')->findOrFail($batchId);

            $items = $batch->guides->flatMap(fn ($guide) => $guide->items)->values();

            if ($items->isEmpty()) {
                throw new RuntimeException('O lote nao possui itens para registrar retorno.');
            }

            $payload = $items->map(function ($item, int $index): array {
                $claimedAmount = round((float) $item->claimed_amount, 2);

                if ($index === 0) {
                    return [
                        'id' => $item->id,
                        'approved_quantity' => $item->claimed_quantity,
                        'approved_amount' => round($claimedAmount / 2, 2),
                        'received_amount' => round($claimedAmount / 2, 2),
                        'gloss_reason' => 'Glosa parcial registrada para auditoria interna.',
                    ];
                }

                return [
                    'id' => $item->id,
                    'approved_quantity' => $item->claimed_quantity,
                    'approved_amount' => $claimedAmount,
                    'received_amount' => $claimedAmount,
                ];
            })->all();

            $service->registerBatchReturn(
                batch: $batch,
                itemPayloads: $payload,
                payload: ['message' => 'Retorno parcial com glosa registrado.'],
            );

            $this->loadData($service);

            Notification::make()
                ->title('Retorno parcial registrado no lote.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function createRepresentationBatch(InsuranceClaimBillingService $service, int $batchId): void
    {
        try {
            $service->createRepresentationBatch(
                sourceBatch: InsuranceClaimBatch::query()->with('guides.items.representations')->findOrFail($batchId),
                createdByUserId: auth()->id(),
            );

            $this->loadData($service);

            Notification::make()
                ->title('Lote de reapresentacao criado com sucesso.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('financeiro.update') === true;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = InsuranceClaimBatch::query()
            ->whereIn('status', ['draft', 'submitted', 'partial_gloss'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() !== null ? 'warning' : null;
    }

    private function loadData(InsuranceClaimBillingService $service): void
    {
        $unitId = ($this->filters['unit_id'] ?? null) ? (int) $this->filters['unit_id'] : null;

        $this->summary = $service->summary($unitId);
        $this->pendingGroups = $service->pendingExecutionGroups($unitId)->all();
        $this->recentBatches = $service->recentBatches(
            unitId: $unitId,
            status: $this->filters['status'] ?: null,
        )->map(fn (InsuranceClaimBatch $batch) => $batch->toArray())->all();
    }

    private function validateFilters(): void
    {
        $this->filters = Validator::make($this->filters, [
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'status' => ['nullable', 'string', 'in:draft,submitted,partial_gloss,glossed,paid,cancelled'],
        ])->validate();
    }
}
