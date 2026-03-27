<?php

namespace App\Filament\Resources\InsurancePlans\Pages;

use App\Filament\Resources\InsurancePlans\InsurancePlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInsurancePlans extends ManageRecords
{
    protected static string $resource = InsurancePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
