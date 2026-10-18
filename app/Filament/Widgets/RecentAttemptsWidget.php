<?php

namespace App\Filament\Widgets;

use App\Models\AssessmentAttempt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAttemptsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'آخرین آزمون‌های ثبت‌شده کاربران';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AssessmentAttempt::query()->latest('id')->limit(5)
            )
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('نام کاربر')
                    ->searchable(),

                TextColumn::make('version.assessment.name')
                    ->label('آزمون'),

                TextColumn::make('status')
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

                TextColumn::make('result.primary_type')
                    ->label('تیپ شخصیتی')
                    ->badge()
                    ->placeholder('نامشخص')
                    ->color('info'),

                TextColumn::make('started_at')
                    ->label('زمان شروع')
                    ->dateTime('Y-m-d H:i'),
            ])
            ->paginated(false);
    }
}
