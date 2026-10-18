<?php

namespace App\Filament\Resources\QuestionOptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionOptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('ترتیب')
                    ->sortable(),

                TextColumn::make('label')
                    ->label('متن گزینه')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('option_key')
                    ->label('شناسه گزینه')
                    ->badge(),

                TextColumn::make('question.prompt')
                    ->label('سؤال مرتبط')
                    ->limit(40)
                    ->searchable(),
            ])
            ->filters([
                //
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
