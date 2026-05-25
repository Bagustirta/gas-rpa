<?php

namespace App\Filament\Resources\AkunPangkalans\Pages;

use App\Filament\Resources\AkunPangkalans\AkunPangkalanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAkunPangkalan extends EditRecord
{
    protected static string $resource = AkunPangkalanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
