<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('مشخصات اصلی سؤال')
                ->schema([
                    Select::make('assessment_version_id')
                        ->label('نسخه آزمون')
                        ->relationship(
                            name: 'version',
                            titleAttribute: 'version'
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn ($record) => $record->assessment ? "{$record->assessment->name} - نسخه {$record->version}" : "نسخه {$record->version}"
                        )
                        ->required()
                        ->searchable()
                        ->preload(),

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

                    Select::make('type')
                        ->label('نوع سؤال')
                        ->options([
                            'single_choice' => 'تک‌گزینه‌ای (چندگزینه‌ای / لیکرت)',
                        ])
                        ->default('single_choice')
                        ->required(),

                    TextInput::make('metadata.dimension')
                        ->label('بُعد شخصیتی (مانند D, I, S, C)')
                        ->placeholder('مثال: D')
                        ->maxLength(50),

                    Toggle::make('is_required')
                        ->label('پاسخ اجباری است')
                        ->default(true),

                    Textarea::make('prompt')
                        ->label('متن و عبارت سؤال')
                        ->placeholder('متن سؤالی که به کاربر نمایش داده می‌شود...')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('گزینه‌های سؤال')
                ->schema([
                    Repeater::make('options')
                        ->relationship()
                        ->label('گزینه‌ها')
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                        ->schema([
                            TextInput::make('sort_order')
                                ->label('ترتیب')
                                ->numeric()
                                ->integer()
                                ->default(1)
                                ->required(),

                            TextInput::make('option_key')
                                ->label('شناسه گزینه')
                                ->placeholder('مثال: opt_1')
                                ->required(),

                            TextInput::make('label')
                                ->label('متن گزینه')
                                ->placeholder('مثال: کاملاً موافقم')
                                ->required()
                                ->columnSpan(2),

                            KeyValue::make('score_values')
                                ->label('امتیازهای ابعاد')
                                ->helperText('مثال: کلید D با مقدار 5')
                                ->keyLabel('بُعد')
                                ->valueLabel('نمره')
                                ->columnSpanFull(),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->addActionLabel('افزودن گزینه جدید')
                        ->collapsible(),
                ]),
        ]);
    }
}
