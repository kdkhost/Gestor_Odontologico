<?php

namespace App\Filament\Pages;

use App\Models\Unit;
use App\Services\BusinessIntelligenceService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;

class BusinessIntelligenceCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'BI e metas';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'BI e metas';

    protected string $view = 'filament.pages.business-intelligence-center';

    public array $filters = [];

    public array $snapshot = [];

    public array $unitOptions = [];

    public function mount(BusinessIntelligenceService $bi): void
    {
        $this->unitOptions = Unit::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $this->filters = [
            'unit_id' => auth()->user()?->hasRole('superadmin') ? null : auth()->user()?->unit_id,
            'from' => now(config('app.timezone'))->subDays(29)->toDateString(),
            'to' => now(config('app.timezone'))->toDateString(),
        ];

        $this->loadSnapshot($bi);
    }

    public function refreshSnapshot(BusinessIntelligenceService $bi): void
    {
        $this->validateFilters();
        $this->loadSnapshot($bi);

        Notification::make()
            ->title('Leitura gerencial atualizada.')
            ->success()
            ->send();
    }

    public function exportUrl(string $section): string
    {
        return route('admin.bi.export', [
            'section' => $section,
            'unit_id' => $this->filters['unit_id'],
            'from' => $this->filters['from'],
            'to' => $this->filters['to'],
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('financeiro.export') === true;
    }

    private function loadSnapshot(BusinessIntelligenceService $bi): void
    {
        $this->snapshot = $bi->snapshot(
            unitId: $this->filters['unit_id'] ? (int) $this->filters['unit_id'] : null,
            fromDate: $this->filters['from'],
            toDate: $this->filters['to'],
        );
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
