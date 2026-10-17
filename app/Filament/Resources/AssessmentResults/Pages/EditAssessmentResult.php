<?php

namespace App\Filament\Resources\AssessmentResults\Pages;

use App\Filament\Resources\AssessmentResults\AssessmentResultResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentResult extends EditRecord
{
    protected static string $resource = AssessmentResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
