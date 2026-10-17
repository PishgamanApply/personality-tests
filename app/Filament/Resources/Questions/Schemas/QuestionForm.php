<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('assessment_version_id')
                ->label('نسخه آزمون')
                ->relationship(
                    name: 'version',
                    titleAttribute: 'version'
                )
                ->required()
                ->searchable()
                ->preload(),

            TextInput::make('question_key')
                ->label('شناسه سؤال')
                ->required()
                ->maxLength(255),

            Textarea::make('prompt')
                ->label('متن سؤال')
                ->required()
                ->rows(4),

            Select::make('type')
                ->label('نوع سؤال')
                ->options([
                    'single_choice' => 'تک‌گزینه‌ای',
                ])
                ->default('single_choice')
                ->required(),

            TextInput::make('sort_order')
                ->label('ترتیب نمایش')
                ->numeric()
                ->integer()
                ->default(0)
                ->required(),

            Toggle::make('is_required')
                ->label('پاسخ اجباری است')
                ->default(true),
        ]);
    }
}
