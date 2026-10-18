<?php

namespace App\Filament\Resources\AssessmentVersions\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'سؤالات این نسخه';

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

                TextInput::make('question_key')
                    ->label('شناسه سؤال (Key)')
                    ->placeholder('مثال: q1')
                    ->required()
                    ->maxLength(255),

                Textarea::make('prompt')
                    ->label('متن و صورت سؤال')
                    ->placeholder('عبارت یا سؤال مورد نظر را وارد نمایید...')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('type')
                    ->label('نوع سؤال')
                    ->options([
                        'single_choice' => 'تک‌گزینه‌ای (لیکرت / چندگزینه‌ای)',
                    ])
                    ->default('single_choice')
                    ->required(),

                TextInput::make('metadata.dimension')
                    ->label('بُعد شخصیتی (اختیاری - مانند D, I, S, C)')
                    ->placeholder('مثال: D')
                    ->maxLength(50),

                Toggle::make('is_required')
                    ->label('پاسخ اجباری است')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question_key')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('ترتیب')
                    ->sortable(),

                TextColumn::make('prompt')
                    ->label('متن سؤال')
                    ->limit(60)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('question_key')
                    ->label('شناسه')
                    ->badge(),

                TextColumn::make('options_count')
                    ->counts('options')
                    ->label('تعداد گزینه‌ها')
                    ->sortable(),

                IconColumn::make('is_required')
                    ->label('اجباری')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('افزودن سؤال جدید'),
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
