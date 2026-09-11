<?php

namespace App\Filament\Resources\VentaCuotaResource\Pages;

use App\Filament\Resources\VentaCuotaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVentaCuotas extends ListRecords
{
    protected static string $resource = VentaCuotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
