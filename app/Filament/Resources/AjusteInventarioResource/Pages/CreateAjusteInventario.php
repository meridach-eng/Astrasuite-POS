<?php

namespace App\Filament\Resources\AjusteInventarioResource\Pages;

use App\Filament\Resources\AjusteInventarioResource;
use App\Models\Lote;
use App\Models\Producto;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateAjusteInventario extends CreateRecord
{
    protected static string $resource = AjusteInventarioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $ajuste = $this->record;

        DB::transaction(function () use ($ajuste) {
            $ajuste->load('detalles');
            $costoTotal = 0;

            foreach ($ajuste->detalles as $detalle) {
                $producto = Producto::lockForUpdate()->find($detalle->producto_id);
                if (! $producto || $producto->tipo !== 'BIEN') {
                    continue;
                }

                $sucursalId = $ajuste->sucursal_id;
                $diferencia = (float) $detalle->diferencia; // (+ sobrante, - faltante)
                $costoUnitario = (float) $detalle->costo_unitario;

                // 1. Aplicar diferencia al stock general de la sucursal
                $pivot = $producto->sucursales()->where('sucursal_id', $sucursalId)->first();
                if ($pivot) {
                    $stockActual = max(0, (float) $pivot->pivot->stock_actual + $diferencia);
                    $producto->sucursales()->updateExistingPivot($sucursalId, [
                        'stock_actual' => $stockActual,
                    ]);
                }

                // 2. Si maneja lotes y se especificó lote
                if ($producto->maneja_lotes && $detalle->lote_id) {
                    $lote = Lote::lockForUpdate()->find($detalle->lote_id);
                    if ($lote) {
                        $nuevoLoteStock = max(0, (float) $lote->cantidad_actual + $diferencia);
                        $lote->update(['cantidad_actual' => $nuevoLoteStock]);
                    }
                }

                $subtotal = round(abs($diferencia) * $costoUnitario, 2);
                $detalle->update(['subtotal' => $subtotal]);
                $costoTotal += $subtotal;
            }

            $ajuste->update(['costo_total' => round($costoTotal, 2)]);
        });
    }
}