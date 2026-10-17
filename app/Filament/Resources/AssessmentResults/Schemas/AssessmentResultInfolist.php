<?php

namespace App\Filament\Resources\AssessmentResults\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AssessmentResultInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('assessment_attempt_id')
                    ->numeric(),
                TextEntry::make('scores')
                    ->columnSpanFull(),
                TextEntry::make('primary_type')
                    ->placeholder('-'),
                TextEntry::make('report')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('calculated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
