<?php

namespace App\Filament\Resources\AssessmentResults\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssessmentResultInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات عمومی ارزیابی')
                    ->schema([
                        TextEntry::make('id')
                            ->label('شناسه نتیجه'),

                        TextEntry::make('attempt.id')
                            ->label('شناسه آزمون والد'),

                        TextEntry::make('attempt.user.name')
                            ->label('نام شرکت‌کننده'),

                        TextEntry::make('attempt.user.email')
                            ->label('ایمیل شرکت‌کننده'),

                        TextEntry::make('attempt.version.assessment.name')
                            ->label('نام آزمون'),

                        TextEntry::make('attempt.version.version')
                            ->label('نسخه آزمون')
                            ->badge(),

                        TextEntry::make('calculated_at')
                            ->label('زمان محاسبه نهایی')
                            ->dateTime('Y-m-d H:i:s'),
                    ])
                    ->columns(4),

                Section::make('نتایج تحلیل و نمرات روان‌سنجی')
                    ->schema([
                        TextEntry::make('primary_type')
                            ->label('تیپ شخصیتی غالب')
                            ->badge()
                            ->color('primary')
                            ->placeholder('نامشخص'),

                        TextEntry::make('scores')
                            ->label('نمرات کسب‌شده در هر بُعد')
                            ->formatStateUsing(function ($state) {
                                if (! is_array($state)) {
                                    return $state;
                                }
                                $parts = [];
                                foreach ($state as $dim => $score) {
                                    $parts[] = "بُعد {$dim}: {$score}";
                                }

                                return implode(' | ', $parts);
                            }),

                        TextEntry::make('report')
                            ->label('گزارش تحلیلی و تفسیر بالینی / رفتاری')
                            ->placeholder('متن گزارشی ثبت نشده است')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
