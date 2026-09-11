<?php

namespace App\Filament\Resources\GastoCategoriaResource\Pages;

use App\Filament\Resources\GastoCategoriaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGastoCategorias extends ListRecords
{
    protected static string $resource = GastoCategoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
