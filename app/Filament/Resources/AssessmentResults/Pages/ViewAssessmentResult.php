<?php

namespace App\Filament\Resources\AssessmentResults\Pages;

use App\Filament\Resources\AssessmentResults\AssessmentResultResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAssessmentResult extends ViewRecord
{
    protected static string $resource = AssessmentResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
