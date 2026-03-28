<?php

namespace App\Filament\Pages;

use App\Services\ClinicalGovernanceService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ClinicalGovernanceCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Governanca clinica';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestao';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Central de governanca clinica';

    protected string $view = 'filament.pages.clinical-governance-center';

    public array $snapshot = [];

    public function mount(ClinicalGovernanceService $governance): void
    {
        $this->snapshot = $governance->snapshot(limit: 8);
    }

    public function refreshSnapshot(ClinicalGovernanceService $governance): void
    {
        $this->snapshot = $governance->snapshot(limit: 8);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('dashboard.view') === true;
    }
}
