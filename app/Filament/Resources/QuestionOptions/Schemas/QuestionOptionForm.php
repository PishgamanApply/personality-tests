<?php

namespace App\Filament\Resources\QuestionOptions\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QuestionOptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('question_id')
                ->label('سؤال')
                ->relationship('question', 'prompt')
                ->required()
                ->searchable(),

            TextInput::make('option_key')
                ->label('شناسه گزینه')
                ->required()
                ->maxLength(255),

            Textarea::make('label')
                ->label('متن گزینه')
                ->required()
                ->rows(3),

            KeyValue::make('score_values')
                ->label('امتیازهای گزینه')
                ->keyLabel('بُعد شخصیتی')
                ->valueLabel('امتیاز')
                ->addActionLabel('افزودن امتیاز'),

            TextInput::make('sort_order')
                ->label('ترتیب نمایش')
                ->numeric()
                ->integer()
                ->default(0)
                ->required(),
        ]);
    }
}
