<?php

namespace App\Filament\Pages;

use App\Models\CommissionSettlement;
use App\Models\Unit;
use App\Services\CommissionSettlementService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class CommissionSettlementCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Repasses';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Central de repasses';

    protected string $view = 'filament.pages.commission-settlement-center';

    public array $filters = [];

    public array $pendingCandidates = [];

    public array $recentSettlements = [];

    public array $unitOptions = [];

    public function mount(CommissionSettlementService $settlements): void
    {
        $this->unitOptions = Unit::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $this->filters = [
            'unit_id' => auth()->user()?->hasRole('superadmin') ? null : auth()->user()?->unit_id,
            'from' => now(config('app.timezone'))->startOfMonth()->toDateString(),
            'to' => now(config('app.timezone'))->endOfMonth()->toDateString(),
        ];

        $this->loadData($settlements);
    }

    public function refreshData(CommissionSettlementService $settlements): void
    {
        $this->validateFilters();
        $this->loadData($settlements);

        Notification::make()
            ->title('Central de repasses atualizada.')
            ->success()
            ->send();
    }

    public function createSettlement(CommissionSettlementService $settlements, int $professionalId, int $unitId = 0): void
    {
        try {
            $settlements->createSettlement(
                professionalId: $professionalId,
                unitId: $unitId > 0 ? $unitId : null,
                fromDate: $this->filters['from'],
                toDate: $this->filters['to'],
                createdByUserId: auth()->id(),
            );

            $this->loadData($settlements);

            Notification::make()
                ->title('Repasse fechado com sucesso.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function markAsPaid(CommissionSettlementService $settlements, int $settlementId): void
    {
        $settlement = CommissionSettlement::query()->findOrFail($settlementId);

        try {
            $settlements->registerPayment($settlement, [
                'payment_method' => 'manual',
                'paid_by_user_id' => auth()->id(),
            ]);
            $this->loadData($settlements);

            Notification::make()
                ->title('Repasse marcado como pago.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelSettlement(CommissionSettlementService $settlements, int $settlementId): void
    {
        $settlement = CommissionSettlement::query()->findOrFail($settlementId);

        try {
            $settlements->cancel($settlement);
            $this->loadData($settlements);

            Notification::make()
                ->title('Repasse cancelado e comissões devolvidas para pendência.')
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

    private function loadData(CommissionSettlementService $settlements): void
    {
        $unitId = $this->filters['unit_id'] ? (int) $this->filters['unit_id'] : null;

        $this->pendingCandidates = $settlements
            ->pendingCandidates($unitId, $this->filters['from'], $this->filters['to'])
            ->values()
            ->all();

        $this->recentSettlements = $settlements
            ->recentSettlements($unitId)
            ->map(fn (CommissionSettlement $settlement) => $settlement->toArray())
            ->all();
    }

    private function validateFilters(): void
    {
        $this->filters = Validator::make($this->filters, [
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
        ])->validate();
    }
}
