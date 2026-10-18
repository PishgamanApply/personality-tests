<?php

namespace App\Filament\Resources\Assessments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'نسخه‌های این آزمون';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('version')
                    ->label('شماره نسخه')
                    ->placeholder('مثال: 1.0')
                    ->required()
                    ->maxLength(255),

                TextInput::make('time_limit_minutes')
                    ->label('محدودیت زمان (دقیقه)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(240)
                    ->default(15)
                    ->required(),

                DateTimePicker::make('published_at')
                    ->label('زمان انتشار')
                    ->nullable(),

                Textarea::make('scoring_config')
                    ->label('پیکربندی امتیازدهی (JSON)')
                    ->helperText('تنظیمات ابعاد، مقیاس و بازه نمرات به فرمت JSON')
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state)
                    ->mutateDehydratedStateUsing(function ($state) {
                        if (empty($state)) {
                            return null;
                        }
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);

                            return json_last_error() === JSON_ERROR_NONE ? $decoded : $state;
                        }

                        return $state;
                    })
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->columns([
                TextColumn::make('version')
                    ->label('شماره نسخه')
                    ->sortable()
                    ->searchable(),

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
            ->headerActions([
                CreateAction::make()->label('افزودن نسخه جدید'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
