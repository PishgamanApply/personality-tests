<?php

namespace App\Filament\Resources\Assessments\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AssessmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام آزمون')
                    ->placeholder('مثال: آزمون شخصیت‌شناسی DISC')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('شناسه یکتا (Slug)')
                    ->placeholder('مثال: disc')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('شناسه یکتا برای دسترسی به آزمون در API و سیستم'),

                Textarea::make('description')
                    ->label('توضیحات و معرفی آزمون')
                    ->placeholder('توضیحاتی در رابطه با اهداف آزمون، ابعاد و روش تحلیل...')
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('آزمون فعال است')
                    ->helperText('در صورت غیرفعال بودن، امکان شروع آزمون جدید توسط کاربران وجود نخواهد داشت.')
                    ->default(true),
            ]);
    }
}
