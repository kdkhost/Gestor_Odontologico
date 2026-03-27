<?php

namespace App\Filament\Pages;

use App\Services\OperationsInsightService;
use BackedEnum;
use Filament\Pages\Page;

class OperationalCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Central operacional';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Central operacional';

    protected string $view = 'filament.pages.operational-center';

    public array $snapshot = [];

    public function mount(OperationsInsightService $insights): void
    {
        $this->snapshot = $insights->snapshot(days: 30, limit: 8);
    }

    public function refreshInsights(OperationsInsightService $insights): void
    {
        $this->snapshot = $insights->snapshot(days: 30, limit: 8);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('dashboard.view') === true;
    }
}
