<?php

namespace App\Filament\Resources\AkunPangkalans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AkunPangkalanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_pangkalan')
                    ->required(),
                TextInput::make('email')
                    ->label('Email / No. Telepon')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(),
            ]);
    }
}
