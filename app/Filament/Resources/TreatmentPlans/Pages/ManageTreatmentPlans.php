<?php

namespace App\Filament\Resources\TreatmentPlans\Pages;

use App\Filament\Resources\TreatmentPlans\TreatmentPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTreatmentPlans extends ManageRecords
{
    protected static string $resource = TreatmentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
