<?php

namespace App\Filament\Resources\AssessmentVersions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssessmentVersionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assessment.name')
                    ->label('آزمون')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('version')
                    ->label('شماره نسخه')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('تعداد سؤالات')
                    ->sortable(),

                TextColumn::make('time_limit_minutes')
                    ->label('مدت آزمون')
                    ->suffix(' دقیقه')
                    ->sortable(),

                TextColumn::make('published_at')
                    ->label('زمان انتشار')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('پیش‌نویس')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('assessment_id')
                    ->label('فیلتر بر اساس آزمون')
                    ->relationship('assessment', 'name')
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
