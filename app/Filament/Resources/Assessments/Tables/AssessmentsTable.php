<?php

namespace App\Filament\Resources\Assessments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AssessmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('نام آزمون')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('شناسه (Slug)')
                    ->badge()
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('versions_count')
                    ->counts('versions')
                    ->label('تعداد نسخه‌ها')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاریخ تعریف')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('وضعیت آزمون')
                    ->placeholder('همه')
                    ->trueLabel('فقط فعال‌ها')
                    ->falseLabel('فقط غیرفعال‌ها'),
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
