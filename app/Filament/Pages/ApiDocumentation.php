<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ApiDocumentation extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracketSquare;

    protected static UnitEnum|string|null $navigationGroup = 'مستندات و یکپارچه‌سازی';

    protected static ?string $navigationLabel = 'مستندات API (Swagger)';

    protected static ?string $title = 'مستندات تعاملی وب‌سرویس‌ها (Swagger UI)';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.api-documentation';

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('openFullPage')
                ->label('مشاهده در تب جدید (تمام‌صفحه)')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(route('docs.swagger'), shouldOpenInNewTab: true)
                ->color('primary'),

            Action::make('downloadYaml')
                ->label('دانلود openapi.yaml')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->url(route('docs.openapi'))
                ->color('gray'),

            Action::make('viewGuide')
                ->label('راهنمای یکپارچه‌سازی')
                ->icon(Heroicon::OutlinedDocumentText)
                ->url(route('docs.guide'), shouldOpenInNewTab: true)
                ->color('gray'),
        ];
    }

    public function getApiBaseUrl(): string
    {
        return url('/api');
    }

    public function getServerKey(): string
    {
        return (string) config('services.sso.server_key', 'تنظیم نشده در .env');
    }

    public function getSourceSystem(): string
    {
        return (string) config('services.sso.source_system', 'main_system');
    }
}
