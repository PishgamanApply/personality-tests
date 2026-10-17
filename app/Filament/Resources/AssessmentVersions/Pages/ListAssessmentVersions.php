<?php

namespace App\Filament\Resources\AssessmentVersions\Pages;

use App\Filament\Resources\AssessmentVersions\AssessmentVersionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentVersions extends ListRecords
{
    protected static string $resource = AssessmentVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
