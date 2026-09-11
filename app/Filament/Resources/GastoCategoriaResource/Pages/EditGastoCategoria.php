<?php

namespace App\Filament\Resources\GastoCategoriaResource\Pages;

use App\Filament\Resources\GastoCategoriaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGastoCategoria extends EditRecord
{
    protected static string $resource = GastoCategoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
