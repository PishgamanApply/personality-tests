<?php

namespace App\Filament\Resources\AssessmentVersions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AssessmentVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('assessment_id')
                ->label('آزمون والد')
                ->relationship('assessment', 'name')
                ->required()
                ->searchable()
                ->preload(),

            TextInput::make('version')
                ->label('شماره نسخه')
                ->placeholder('مثال: 1.0')
                ->required()
                ->maxLength(255),

            TextInput::make('time_limit_minutes')
                ->label('محدودیت زمان آزمون (دقیقه)')
                ->numeric()
                ->minValue(1)
                ->maxValue(240)
                ->default(15)
                ->required(),

            DateTimePicker::make('published_at')
                ->label('تاریخ و زمان انتشار')
                ->helperText('در صورت خالی بودن، نسخه به صورت پیش‌نویس در نظر گرفته می‌شود.')
                ->nullable(),

            Textarea::make('scoring_config')
                ->label('پیکربندی امتیازدهی و ابعاد (JSON)')
                ->helperText('فرمت JSON شامل ابعاد (dimensions)، مقیاس (scale_type) و تنظیمات نمره‌گذاری')
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
                ->rows(5)
                ->columnSpanFull(),
        ]);
    }
}
