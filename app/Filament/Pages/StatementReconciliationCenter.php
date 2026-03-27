<?php

namespace App\Filament\Pages;

use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\Unit;
use App\Services\BankStatementImportService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;
use RuntimeException;

class StatementReconciliationCenter extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationLabel = 'Extrato e conciliacao';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestao';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Importacao de extrato e conciliacao';

    protected string $view = 'filament.pages.statement-reconciliation-center';

    public array $importState = [];

    public array $recentImports = [];

    public array $suggestions = [];

    public array $unitOptions = [];

    public array $fileTypeOptions = [];

    public array $bankProfileOptions = [];

    public $statementFile = null;

    public function mount(BankStatementImportService $imports): void
    {
        $this->unitOptions = Unit::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
        $this->fileTypeOptions = $imports->fileTypeOptions();
        $this->bankProfileOptions = $imports->bankProfileOptions();

        $this->importState = [
            'unit_id' => auth()->user()?->hasRole('superadmin') ? null : auth()->user()?->unit_id,
            'file_type' => 'auto',
            'delimiter' => 'auto',
            'bank_profile' => 'generic',
            'has_header' => true,
        ];

        $this->loadData($imports);
    }

    public function importStatement(BankStatementImportService $imports): void
    {
        $data = Validator::make([
            'unit_id' => $this->importState['unit_id'] ?? null,
            'file_type' => $this->importState['file_type'] ?? 'auto',
            'delimiter' => $this->importState['delimiter'] ?? 'auto',
            'bank_profile' => $this->importState['bank_profile'] ?? 'generic',
            'has_header' => $this->importState['has_header'] ?? true,
            'statement_file' => $this->statementFile,
        ], [
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'file_type' => ['required', Rule::in(array_keys($this->fileTypeOptions))],
            'delimiter' => ['required', Rule::in(['auto', ';', ',', 'tab'])],
            'bank_profile' => ['required', Rule::in(array_keys($this->bankProfileOptions))],
            'has_header' => ['boolean'],
            'statement_file' => ['required', 'file', 'extensions:csv,txt,ofx'],
        ])->validate();

        $storedPath = $this->statementFile->store('statement-imports', 'local');

        $imports->importStoredFile(
            storedPath: $storedPath,
            originalName: $this->statementFile->getClientOriginalName(),
            unitId: $data['unit_id'] ? (int) $data['unit_id'] : null,
            uploadedByUserId: auth()->id(),
            options: [
                'disk' => 'local',
                'file_type' => $data['file_type'],
                'delimiter' => $data['delimiter'],
                'bank_profile' => $data['bank_profile'],
                'has_header' => (bool) $data['has_header'],
            ],
        );

        $this->statementFile = null;
        $this->loadData($imports);

        Notification::make()
            ->title('Extrato importado e sugestoes geradas.')
            ->success()
            ->send();
    }

    public function applySuggestion(BankStatementImportService $imports, int $lineId): void
    {
        try {
            $imports->reconcileLine(
                line: BankStatementLine::query()->findOrFail($lineId),
                reconciledByUserId: auth()->id(),
            );

            $this->loadData($imports);

            Notification::make()
                ->title('Linha do extrato conciliada com sucesso.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function applyImportSuggestions(BankStatementImportService $imports, int $importId): void
    {
        $count = $imports->reconcileImportSuggestions(
            import: BankStatementImport::query()->findOrFail($importId),
            reconciledByUserId: auth()->id(),
        );

        $this->loadData($imports);

        Notification::make()
            ->title($count > 0 ? "Conciliacao assistida aplicada em {$count} linha(s)." : 'Nenhuma sugestao elegivel foi aplicada.')
            ->success()
            ->send();
    }

    public function refreshData(BankStatementImportService $imports): void
    {
        $this->loadData($imports);

        Notification::make()
            ->title('Central de extrato atualizada.')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('financeiro.update') === true;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = BankStatementLine::query()
            ->whereNull('matched_commission_settlement_id')
            ->whereNotNull('suggested_commission_settlement_id')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() !== null ? 'warning' : null;
    }

    private function loadData(BankStatementImportService $imports): void
    {
        $unitId = ($this->importState['unit_id'] ?? null) ? (int) $this->importState['unit_id'] : null;

        $this->recentImports = $imports->latestImports($unitId)
            ->map(fn (BankStatementImport $import) => $import->toArray())
            ->all();

        $this->suggestions = $imports->openSuggestions($unitId)
            ->map(fn (BankStatementLine $line) => $line->toArray())
            ->all();
    }
}
