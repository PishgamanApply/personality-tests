<?php

namespace App\Filament\Resources\AssessmentResults;

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
use UnitEnum;

class AssessmentResultResource extends Resource
{
    protected static ?string $model = AssessmentResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static UnitEnum|string|null $navigationGroup = 'ارزیابی‌ها و نتایج';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'نتیجه ارزیابی';
    }

    public static function getPluralModelLabel(): string
    {
        return 'نتایج تفصیلی';
    }

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
            'view' => ViewAssessmentResult::route('/{record}'),
        ];
    }
}
