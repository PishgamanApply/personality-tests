<?php

namespace App\Filament\Resources\AssessmentVersions\Pages;

use App\Filament\Resources\AssessmentVersions\AssessmentVersionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentVersion extends EditRecord
{
    protected static string $resource = AssessmentVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
