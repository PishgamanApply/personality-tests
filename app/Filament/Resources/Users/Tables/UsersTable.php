<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('نام کاربر')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                IconColumn::make('is_admin')
                    ->label('مدیر ارشد')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('attempts_count')
                    ->counts('attempts')
                    ->label('تعداد ارزیابی‌ها')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاریخ عضویت')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_admin')
                    ->label('نقش کاربر')
                    ->placeholder('همه کاربران')
                    ->trueLabel('فقط مدیران')
                    ->falseLabel('کاربران عادی'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
