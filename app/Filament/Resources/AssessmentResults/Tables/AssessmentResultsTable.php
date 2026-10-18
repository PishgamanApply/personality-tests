<?php

namespace App\Filament\Resources\AssessmentResults\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssessmentResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه نتیجه')
                    ->sortable(),

                TextColumn::make('assessment_attempt_id')
                    ->label('شناسه آزمون')
                    ->sortable(),

                TextColumn::make('attempt.user.name')
                    ->label('کاربر')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('attempt.version.assessment.name')
                    ->label('آزمون')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('primary_type')
                    ->label('تیپ شخصیتی')
                    ->placeholder('نامشخص')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('scores')
                    ->label('امتیازها')
                    ->formatStateUsing(function ($state) {
                        if (! is_array($state)) {
                            return $state;
                        }
                        $parts = [];
                        foreach ($state as $k => $v) {
                            $parts[] = "{$k}: {$v}";
                        }

                        return implode(' | ', $parts);
                    }),

                TextColumn::make('calculated_at')
                    ->label('زمان محاسبه')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('primary_type')
                    ->label('تیپ شخصیتی')
                    ->options([
                        'D' => 'D - تسلط و هدایت‌گری (Dominance)',
                        'I' => 'I - تأثیرگذاری و برون‌گرایی (Influence)',
                        'S' => 'S - ثبات و صبوری (Steadiness)',
                        'C' => 'C - دقت و وظیفه‌شناسی (Conscientiousness)',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
