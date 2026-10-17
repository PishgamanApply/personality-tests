<?php

namespace App\Filament\Resources\AssessmentResults\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssessmentResultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('assessment_attempt_id')
                    ->required()
                    ->numeric(),
                Textarea::make('scores')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('primary_type'),
                Textarea::make('report')
                    ->columnSpanFull(),
                DateTimePicker::make('calculated_at'),
            ]);
    }
}
