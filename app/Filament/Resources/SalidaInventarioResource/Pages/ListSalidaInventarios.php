<?php

namespace App\Filament\Resources\SalidaInventarioResource\Pages;

use App\Filament\Resources\SalidaInventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalidaInventarios extends ListRecords
{
    protected static string $resource = SalidaInventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
