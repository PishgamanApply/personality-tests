<?php

namespace App\Filament\Resources\AssessmentAttempts;

use App\Filament\Resources\AssessmentAttempts\Pages\CreateAssessmentAttempt;
use App\Filament\Resources\AssessmentAttempts\Pages\EditAssessmentAttempt;
use App\Filament\Resources\AssessmentAttempts\Pages\ListAssessmentAttempts;
use App\Filament\Resources\AssessmentAttempts\Schemas\AssessmentAttemptForm;
use App\Filament\Resources\AssessmentAttempts\Tables\AssessmentAttemptsTable;
use App\Models\AssessmentAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\AssessmentAttempts\RelationManagers;

class AssessmentAttemptResource extends Resource
{
    protected static ?string $model = AssessmentAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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

    // public static function getPages(): array
    // {
    //     return [
    //         'index' => ListAssessmentAttempts::route('/'),
    //         'create' => CreateAssessmentAttempt::route('/create'),
    //         'edit' => EditAssessmentAttempt::route('/{record}/edit'),
    //     ];
    // }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssessmentAttempts::route('/'),
            'view' => Pages\ViewAssessmentAttempt::route('/{record}'),
        ];
    }

    
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('id')
                ->label('شناسه تلاش'),

            TextEntry::make('user.name')
                ->label('نام کاربر'),

            TextEntry::make('version.assessment.name')
                ->label('نام آزمون'),

            TextEntry::make('version.version')
                ->label('نسخه آزمون'),

            TextEntry::make('status')
                ->label('وضعیت')
                ->badge(),

            TextEntry::make('started_at')
                ->label('زمان شروع')
                ->dateTime('Y-m-d H:i:s'),

            TextEntry::make('completed_at')
                ->label('زمان پایان')
                ->dateTime('Y-m-d H:i:s')
                ->placeholder('هنوز پایان نیافته'),

            TextEntry::make('result.primary_type')
                ->label('تیپ اصلی')
                ->placeholder('هنوز محاسبه نشده'),

            TextEntry::make('result.scores')
                ->label('امتیازها')
                ->formatStateUsing(
                    fn ($state) => is_array($state)
                        ? json_encode(
                            $state,
                            JSON_UNESCAPED_UNICODE
                        )
                        : $state
                )
                ->placeholder('نتیجه‌ای ثبت نشده'),
        ]);
    }


}
