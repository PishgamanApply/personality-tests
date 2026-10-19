<?php

namespace App\Filament\Resources\AssessmentAttempts\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssessmentAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('کاربر')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('version.assessment.name')
                    ->label('آزمون')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('version.version')
                    ->label('نسخه')
                    ->badge(),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'completed' => 'تکمیل‌شده',
                        'in_progress' => 'در حال انجام',
                        'timed_out' => 'پایان مهلت (منقضی‌شده)',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'warning',
                        'timed_out' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('result.primary_type')
                    ->label('تیپ شخصیتی')
                    ->badge()
                    ->placeholder('نامشخص')
                    ->color('info'),

                TextColumn::make('started_at')
                    ->label('زمان شروع')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('زمان پایان')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت آزمون')
                    ->options([
                        'in_progress' => 'در حال انجام',
                        'completed' => 'تکمیل‌شده',
                        'timed_out' => 'پایان مهلت (منقضی‌شده)',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
