<?php

namespace App\Filament\Resources\AjusteInventarioResource\Pages;

use App\Filament\Resources\AjusteInventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAjusteInventario extends ViewRecord
{
    protected static string $resource = AjusteInventarioResource::class;

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