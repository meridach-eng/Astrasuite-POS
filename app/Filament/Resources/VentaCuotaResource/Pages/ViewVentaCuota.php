<?php

namespace App\Filament\Resources\VentaCuotaResource\Pages;

use App\Filament\Resources\CuotaCobroResource;
use App\Filament\Resources\VentaCuotaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewVentaCuota extends ViewRecord
{
    protected static string $resource = VentaCuotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('imprimir_contrato')
                ->label('Descargar Contrato A4 (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn (): string => route('ventas-cuotas.pdf', $this->record->id), shouldOpenInNewTab: true),

            Action::make('ir_a_cobros')
                ->label('Gestionar / Cobrar Cuotas')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->url(fn (): string => CuotaCobroResource::getUrl('index', [
                    'tableFilters' => [
                        'venta_cuota_id' => [
                            'value' => $this->record->id,
                        ],
                    ],
                ])),
        ];
    }
}