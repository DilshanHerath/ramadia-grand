<?php

namespace App\Filament\Resources\Invites\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class InviteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('company'),
                TextInput::make('number_of_invites')
                    ->numeric(),
                TextInput::make('contact'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('table_no'),
                TextInput::make('ticket_status')
                    ->required()
                    ->default('Pending'),
                Toggle::make('scan_status')
                    ->required(),
                TextInput::make('qr_code'),
            ]);
    }
}
