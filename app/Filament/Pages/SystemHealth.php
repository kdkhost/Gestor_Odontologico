<?php

namespace App\Filament\Pages;

use App\Services\SystemHealthService;
use BackedEnum;
use Filament\Pages\Page;

class SystemHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'Central técnica';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Central técnica';

    protected string $view = 'filament.pages.system-health';

    public array $snapshot = [];

    public function mount(SystemHealthService $health): void
    {
        $this->snapshot = $health->snapshot();
    }

    public function refreshSnapshot(SystemHealthService $health): void
    {
        $this->snapshot = $health->snapshot();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('configuracoes.manage') === true;
    }
}
