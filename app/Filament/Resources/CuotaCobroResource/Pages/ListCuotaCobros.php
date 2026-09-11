<?php

namespace App\Filament\Resources\CuotaCobroResource\Pages;

use App\Filament\Resources\CuotaCobroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCuotaCobros extends ListRecords
{
    protected static string $resource = CuotaCobroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
