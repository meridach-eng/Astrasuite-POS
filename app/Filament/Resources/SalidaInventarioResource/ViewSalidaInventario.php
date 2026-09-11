<?php

namespace App\Filament\Resources\SalidaInventarioResource\Pages;

use App\Filament\Resources\SalidaInventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSalidaInventario extends ViewRecord
{
    protected static string $resource = SalidaInventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('Volver al Listado')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(static::$resource::getUrl('index')),
        ];
    }
}