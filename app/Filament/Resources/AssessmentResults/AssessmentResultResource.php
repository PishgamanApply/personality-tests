<?php

namespace App\Filament\Resources\AssessmentResults;

use App\Filament\Resources\AssessmentResults\Pages\CreateAssessmentResult;
use App\Filament\Resources\AssessmentResults\Pages\EditAssessmentResult;
use App\Filament\Resources\AssessmentResults\Pages\ListAssessmentResults;
use App\Filament\Resources\AssessmentResults\Pages\ViewAssessmentResult;
use App\Filament\Resources\AssessmentResults\Schemas\AssessmentResultForm;
use App\Filament\Resources\AssessmentResults\Schemas\AssessmentResultInfolist;
use App\Filament\Resources\AssessmentResults\Tables\AssessmentResultsTable;
use App\Models\AssessmentResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssessmentResultResource extends Resource
{
    protected static ?string $model = AssessmentResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AssessmentResultForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AssessmentResultInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssessmentResultsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssessmentResults::route('/'),
            // 'create' => CreateAssessmentResult::route('/create'),
            // 'view' => ViewAssessmentResult::route('/{record}'),
            // 'edit' => EditAssessmentResult::route('/{record}/edit'),
        ];
    }
}
