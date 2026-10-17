<?php

namespace App\Filament\Resources\AssessmentAttempts\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'answers';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('question_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question.question_key')
                    ->label('کد سؤال'),

                TextColumn::make('question.prompt')
                    ->label('متن سؤال')
                    ->wrap(),

                TextColumn::make('selectedOption.label')
                    ->label('گزینه انتخاب‌شده')
                    ->placeholder('بدون گزینه'),

                TextColumn::make('answer_value')
                    ->label('پاسخ متنی')
                    ->formatStateUsing(
                        fn ($state) => is_array($state)
                            ? json_encode(
                                $state,
                                JSON_UNESCAPED_UNICODE
                            )
                            : $state
                    )
                    ->placeholder('—'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

}
