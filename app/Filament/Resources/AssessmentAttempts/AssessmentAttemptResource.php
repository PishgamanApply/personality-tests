<?php

namespace App\Filament\Resources\AssessmentAttempts;

use App\Filament\Resources\AssessmentAttempts\Pages\ListAssessmentAttempts;
use App\Filament\Resources\AssessmentAttempts\Schemas\AssessmentAttemptForm;
use App\Filament\Resources\AssessmentAttempts\Tables\AssessmentAttemptsTable;
use App\Models\AssessmentAttempt;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AssessmentAttemptResource extends Resource
{
    protected static ?string $model = AssessmentAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static UnitEnum|string|null $navigationGroup = 'ارزیابی‌ها و نتایج';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'آزمون کاربر';
    }

    public static function getPluralModelLabel(): string
    {
        return 'آزمون‌های کاربران';
    }

    public static function form(Schema $schema): Schema
    {
        return AssessmentAttemptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssessmentAttemptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AnswersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssessmentAttempts::route('/'),
            'view' => Pages\ViewAssessmentAttempt::route('/{record}'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('مشخصات آزمون و کاربر')
                ->schema([
                    TextEntry::make('id')
                        ->label('شناسه آزمون'),

                    TextEntry::make('user.name')
                        ->label('نام کاربر'),

                    TextEntry::make('user.email')
                        ->label('ایمیل کاربر'),

                    TextEntry::make('version.assessment.name')
                        ->label('نام آزمون'),

                    TextEntry::make('version.version')
                        ->label('نسخه آزمون')
                        ->badge(),

                    TextEntry::make('status')
                        ->label('وضعیت')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'completed' => 'تکمیل‌شده',
                            'in_progress' => 'در حال انجام',
                            default => $state,
                        })
                        ->color(fn (string $state): string => match ($state) {
                            'completed' => 'success',
                            'in_progress' => 'warning',
                            default => 'gray',
                        }),

                    TextEntry::make('started_at')
                        ->label('زمان شروع')
                        ->dateTime('Y-m-d H:i:s'),

                    TextEntry::make('completed_at')
                        ->label('زمان پایان')
                        ->dateTime('Y-m-d H:i:s')
                        ->placeholder('هنوز پایان نیافته'),
                ])
                ->columns(4),

            Section::make('نتیجه و تحلیل روان‌سنجی')
                ->schema([
                    TextEntry::make('result.primary_type')
                        ->label('تیپ شخصیتی اصلی')
                        ->badge()
                        ->color('primary')
                        ->placeholder('هنوز محاسبه نشده'),

                    TextEntry::make('result.scores')
                        ->label('امتیازهای کسب‌شده')
                        ->formatStateUsing(function ($state) {
                            if (! is_array($state)) {
                                return $state;
                            }
                            $output = [];
                            foreach ($state as $dim => $score) {
                                $output[] = "بُعد {$dim}: {$score}";
                            }

                            return implode(' | ', $output);
                        })
                        ->placeholder('نتیجه‌ای ثبت نشده'),

                    TextEntry::make('result.report')
                        ->label('متن گزارش و تفسیر روان‌شناختی')
                        ->placeholder('گزارشی تولید نشده است')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
