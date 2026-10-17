<?php

namespace App\Filament\Resources\AssessmentAttempts\Pages;

use App\Filament\Resources\AssessmentAttempts\AssessmentAttemptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentAttempt extends EditRecord
{
    protected static string $resource = AssessmentAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
