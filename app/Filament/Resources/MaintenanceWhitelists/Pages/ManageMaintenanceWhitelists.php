<?php

namespace App\Filament\Resources\MaintenanceWhitelists\Pages;

use App\Filament\Resources\MaintenanceWhitelists\MaintenanceWhitelistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMaintenanceWhitelists extends ManageRecords
{
    protected static string $resource = MaintenanceWhitelistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
