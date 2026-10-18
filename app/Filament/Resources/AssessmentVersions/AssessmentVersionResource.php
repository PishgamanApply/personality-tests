<?php

namespace App\Filament\Resources\AssessmentVersions;

use App\Filament\Resources\AssessmentVersions\Pages\CreateAssessmentVersion;
use App\Filament\Resources\AssessmentVersions\Pages\EditAssessmentVersion;
use App\Filament\Resources\AssessmentVersions\Pages\ListAssessmentVersions;
use App\Filament\Resources\AssessmentVersions\Schemas\AssessmentVersionForm;
use App\Filament\Resources\AssessmentVersions\Tables\AssessmentVersionsTable;
use App\Models\AssessmentVersion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AssessmentVersionResource extends Resource
{
    protected static ?string $model = AssessmentVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static UnitEnum|string|null $navigationGroup = 'مدیریت آزمون‌ها';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'version';

    public static function getModelLabel(): string
    {
        return 'نسخه آزمون';
    }

    public static function getPluralModelLabel(): string
    {
        return 'نسخه‌های آزمون';
    }

    public static function form(Schema $schema): Schema
    {
        return AssessmentVersionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssessmentVersionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssessmentVersions::route('/'),
            'create' => CreateAssessmentVersion::route('/create'),
            'edit' => EditAssessmentVersion::route('/{record}/edit'),
        ];
    }
}
