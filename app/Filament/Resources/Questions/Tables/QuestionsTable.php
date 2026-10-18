<?php

namespace App\Filament\Resources\Questions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('ترتیب')
                    ->sortable(),

                TextColumn::make('prompt')
                    ->label('متن سؤال')
                    ->limit(55)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('version.assessment.name')
                    ->label('آزمون')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('version.version')
                    ->label('نسخه')
                    ->badge()
                    ->sortable(),

                TextColumn::make('question_key')
                    ->label('شناسه (Key)')
                    ->badge(),

                TextColumn::make('options_count')
                    ->counts('options')
                    ->label('تعداد گزینه‌ها')
                    ->sortable(),

                IconColumn::make('is_required')
                    ->label('اجباری')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('assessment_version_id')
                    ->label('فیلتر بر اساس نسخه آزمون')
                    ->relationship('version', 'version')
                    ->getOptionLabelFromRecordUsing(
                        fn ($record) => $record->assessment ? "{$record->assessment->name} - v{$record->version}" : "v{$record->version}"
                    )
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
