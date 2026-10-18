<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام و نام خانوادگی')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('ایمیل')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('رمز عبور')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),

                Toggle::make('is_admin')
                    ->label('دسترسی مدیر ارشد (ادمین)')
                    ->helperText('در صورت فعال بودن، کاربر امکان ورود به این پنل مدیریتی را خواهد داشت.')
                    ->default(false),
            ]);
    }
}
