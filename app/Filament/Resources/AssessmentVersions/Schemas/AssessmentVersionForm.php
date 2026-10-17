<?php

namespace App\Filament\Resources\AssessmentVersions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssessmentVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('assessment_id')
                ->label('آزمون')
                ->relationship('assessment', 'name')
                ->required()
                ->searchable()
                ->preload(),

            TextInput::make('version')
                ->label('شماره نسخه')
                ->required()
                ->maxLength(255),

            DateTimePicker::make('published_at')
                ->label('زمان انتشار')
                ->nullable(),
        ]);
    }
}
