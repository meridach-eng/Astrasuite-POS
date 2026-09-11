<?php

namespace App\Filament\Resources\SalidaInventarioResource\Pages;

use App\Filament\Resources\SalidaInventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalidaInventario extends EditRecord
{
    protected static string $resource = SalidaInventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
