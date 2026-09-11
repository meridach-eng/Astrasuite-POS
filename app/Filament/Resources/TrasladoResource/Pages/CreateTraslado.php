<?php

namespace App\Filament\Resources\TrasladoResource\Pages;

use App\Filament\Resources\TrasladoResource;
use App\Models\Lote;
use App\Models\Producto;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTraslado extends CreateRecord
{
    protected static string $resource = TrasladoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['sucursal_origen_id'] === $data['sucursal_destino_id']) {
            throw ValidationException::withMessages([
                'sucursal_destino_id' => 'La sucursal de destino no puede ser la misma que la de origen.',
            ]);
        }

        foreach ($data['detalles'] ?? [] as $detalle) {
            $producto = Producto::find($detalle['producto_id']);
            $pivot = $producto?->sucursales()->where('sucursal_id', $data['sucursal_origen_id'])->first();
            $stockDisponible = $pivot ? (float) $pivot->pivot->stock_actual : 0;

            if ((float) $detalle['cantidad'] > $stockDisponible) {
                throw ValidationException::withMessages([
                    'detalles' => "El producto {$producto->nombre} no tiene suficiente stock en la sucursal de origen. Disponible: {$stockDisponible}",
                ]);
            }
        }

        $data['estado'] = 'ENVIADO';
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $traslado = $this->record;

        DB::transaction(function () use ($traslado) {
            $traslado->load('detalles');
            $origenId = $traslado->sucursal_origen_id;

            foreach ($traslado->detalles as $detalle) {
                $producto = Producto::lockForUpdate()->find($detalle->producto_id);
                if (! $producto) continue;

                $cant = (float) $detalle->cantidad;

                // 1. Descontar stock de origen al crear (congela la mercancía en tránsito)
                $pivot = $producto->sucursales()->where('sucursal_id', $origenId)->first();
                if ($pivot) {
                    $stockActual = max(0, (float) $pivot->pivot->stock_actual - $cant);
                    $producto->sucursales()->updateExistingPivot($origenId, ['stock_actual' => $stockActual]);
                }

                // 2. Descontar lote de origen si aplica
                if ($detalle->lote_id) {
                    $lote = Lote::lockForUpdate()->find($detalle->lote_id);
                    if ($lote) {
                        $lote->decrement('cantidad_actual', min($lote->cantidad_actual, $cant));
                    }
                }
            }
        });
    }
}