<?php

namespace App\Filament\Pages;

use App\Models\AccountReceivable;
use App\Models\FiscalInvoice;
use App\Models\Unit;
use App\Services\FiscalInvoiceService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class FiscalBillingCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Faturamento fiscal';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestao';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Central de faturamento fiscal';

    protected string $view = 'filament.pages.fiscal-billing-center';

    public array $filters = [];

    public array $unitOptions = [];

    public array $summary = [];

    public array $eligibleReceivables = [];

    public array $recentInvoices = [];

    public function mount(FiscalInvoiceService $fiscal): void
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

        $this->loadData($fiscal);
    }

    public function refreshData(FiscalInvoiceService $fiscal): void
    {
        $this->validateFilters();
        $this->loadData($fiscal);

        Notification::make()
            ->title('Central fiscal atualizada.')
            ->success()
            ->send();
    }

    public function createDraft(FiscalInvoiceService $fiscal, int $accountReceivableId): void
    {
        try {
            $fiscal->createDraftForReceivable(
                receivable: AccountReceivable::query()->findOrFail($accountReceivableId),
                payload: ['created_by_user_id' => auth()->id()],
            );

            $this->loadData($fiscal);

            Notification::make()
                ->title('NFSe em rascunho criada com sucesso.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function createAllDrafts(FiscalInvoiceService $fiscal): void
    {
        $count = $fiscal->createDraftsForEligible(
            unitId: $this->filters['unit_id'] ? (int) $this->filters['unit_id'] : null,
            fromDate: $this->filters['from'],
            toDate: $this->filters['to'],
            createdByUserId: auth()->id(),
        );

        $this->loadData($fiscal);

        Notification::make()
            ->title($count > 0 ? "{$count} rascunho(s) fiscal(is) criado(s)." : 'Nenhuma conta elegivel pronta para NFSe.')
            ->success()
            ->send();
    }

    public function queueInvoice(FiscalInvoiceService $fiscal, int $invoiceId): void
    {
        try {
            $fiscal->queueInvoice(FiscalInvoice::query()->findOrFail($invoiceId));
            $this->loadData($fiscal);

            Notification::make()
                ->title('NFSe enviada para fila fiscal.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function submitPending(FiscalInvoiceService $fiscal): void
    {
        $count = $fiscal->submitPending(
            unitId: $this->filters['unit_id'] ? (int) $this->filters['unit_id'] : null,
        );

        $this->loadData($fiscal);

        Notification::make()
            ->title($count > 0 ? "{$count} NFSe(s) enviadas para protocolo." : 'Nenhuma NFSe pendente na fila.')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('financeiro.update') === true;
    }

    private function loadData(FiscalInvoiceService $fiscal): void
    {
        $unitId = $this->filters['unit_id'] ? (int) $this->filters['unit_id'] : null;

        $this->summary = $fiscal->summary(
            unitId: $unitId,
            fromDate: $this->filters['from'],
            toDate: $this->filters['to'],
        );

        $this->eligibleReceivables = $fiscal->eligibleReceivables(
            unitId: $unitId,
            fromDate: $this->filters['from'],
            toDate: $this->filters['to'],
        )->values()->all();

        $this->recentInvoices = $fiscal->recentInvoices($unitId)
            ->map(fn (FiscalInvoice $invoice) => $invoice->toArray())
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
