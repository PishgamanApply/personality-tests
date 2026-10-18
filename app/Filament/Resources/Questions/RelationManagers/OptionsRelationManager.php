<?php

namespace App\Filament\Resources\Questions\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $title = 'گزینه‌های این سؤال';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sort_order')
                    ->label('ترتیب نمایش')
                    ->numeric()
                    ->integer()
                    ->default(1)
                    ->required(),

                TextInput::make('option_key')
                    ->label('شناسه گزینه (Key)')
                    ->placeholder('مثال: likert_1')
                    ->required()
                    ->maxLength(255),

                TextInput::make('label')
                    ->label('متن گزینه')
                    ->placeholder('مثال: کاملاً موافقم')
                    ->required()
                    ->columnSpanFull(),

                KeyValue::make('score_values')
                    ->label('امتیازهای ابعاد شخصیتی')
                    ->helperText('مثال: کلید D با مقدار 5')
                    ->keyLabel('بُعد شخصیتی')
                    ->valueLabel('نمره')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('ترتیب')
                    ->sortable(),

                TextColumn::make('label')
                    ->label('متن گزینه')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('option_key')
                    ->label('شناسه')
                    ->badge(),

                TextColumn::make('score_values')
                    ->label('امتیازها')
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_UNESCAPED_UNICODE) : $state)
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->label('افزودن گزینه جدید'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
