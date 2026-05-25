<?php

namespace App\Filament\Resources\AkunPangkalans\Pages;

use App\Filament\Resources\AkunPangkalans\AkunPangkalanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAkunPangkalans extends ListRecords
{
    protected static string $resource = AkunPangkalanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
