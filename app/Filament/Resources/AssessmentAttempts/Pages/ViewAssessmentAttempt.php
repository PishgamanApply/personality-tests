<?php

namespace App\Filament\Resources\AssessmentAttempts\Pages;

use App\Filament\Resources\AssessmentAttempts\AssessmentAttemptResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAssessmentAttempt extends ViewRecord
{
    protected static string $resource = AssessmentAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
