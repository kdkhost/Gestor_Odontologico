<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\OperationalCenter;
use App\Filament\Resources\AccountReceivables\AccountReceivableResource;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\InventoryItems\InventoryItemResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Services\OperationsInsightService;
use Filament\Widgets\Widget;

class OperationalAlertsWidget extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.operational-alerts-widget';

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.view') === true;
    }

    protected function getViewData(): array
    {
        return [
            'snapshot' => app(OperationsInsightService::class)->snapshot(limit: 4),
            'links' => [
                'appointments' => AppointmentResource::getUrl('index'),
                'receivables' => AccountReceivableResource::getUrl('index'),
                'inventory' => InventoryItemResource::getUrl('index'),
                'patients' => PatientResource::getUrl('index'),
                'operations' => OperationalCenter::getUrl(),
            ],
        ];
    }
}
