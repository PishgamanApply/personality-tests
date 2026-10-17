<?php

namespace App\Filament\Resources\AssessmentAttempts\Pages;

use App\Filament\Resources\AssessmentAttempts\AssessmentAttemptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentAttempts extends ListRecords
{
    protected static string $resource = AssessmentAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
