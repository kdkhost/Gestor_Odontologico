<?php

namespace App\Filament\Resources\AccountReceivables\Pages;

use App\Filament\Resources\AccountReceivables\AccountReceivableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAccountReceivables extends ManageRecords
{
    protected static string $resource = AccountReceivableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
