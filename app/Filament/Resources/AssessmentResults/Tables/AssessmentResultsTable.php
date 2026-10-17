<?php

namespace App\Filament\Resources\AssessmentResults\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssessmentResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه نتیجه')
                    ->sortable(),

                TextColumn::make('assessment_attempt_id')
                    ->label('شناسه تلاش')
                    ->sortable(),

                TextColumn::make('attempt.user.name')
                    ->label('کاربر')
                    ->searchable(),

                TextColumn::make('attempt.version.assessment.name')
                    ->label('آزمون')
                    ->searchable(),

                TextColumn::make('primary_type')
                    ->label('تیپ اصلی')
                    ->placeholder('نامشخص')
                    ->badge(),

                TextColumn::make('scores')
                    ->label('امتیازها')
                    ->formatStateUsing(
                        fn ($state) => is_array($state)
                            ? json_encode(
                                $state,
                                JSON_UNESCAPED_UNICODE
                            )
                            : $state
                    ),

                TextColumn::make('calculated_at')
                    ->label('زمان محاسبه')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('primary_type')
                    ->label('تیپ اصلی')
                    ->options([
                        'D' => 'D - Dominance',
                        'I' => 'I - Influence',
                        'S' => 'S - Steadiness',
                        'C' => 'C - Conscientiousness',
                    ]),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
