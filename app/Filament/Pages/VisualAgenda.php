<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class VisualAgenda extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Agenda visual';

    protected static string|\UnitEnum|null $navigationGroup = 'Operação';

    protected static ?string $title = 'Agenda visual';

    protected string $view = 'filament.pages.visual-agenda';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('agenda.view') === true;
    }
}
