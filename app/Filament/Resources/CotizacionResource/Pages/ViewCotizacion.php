<?php

namespace App\Filament\Resources\CotizacionResource\Pages;

use App\Filament\Resources\CotizacionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCotizacion extends ViewRecord
{
    protected static string $resource = CotizacionResource::class;

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