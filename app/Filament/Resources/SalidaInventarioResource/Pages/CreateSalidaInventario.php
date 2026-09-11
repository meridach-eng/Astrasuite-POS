<?php

namespace App\Filament\Resources\SalidaInventarioResource\Pages;

use App\Filament\Resources\SalidaInventarioResource;
use App\Models\Lote;
use App\Models\Producto;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateSalidaInventario extends CreateRecord
{
    protected static string $resource = SalidaInventarioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $salida = $this->record;

        DB::transaction(function () use ($salida) {
            $salida->load('detalles');
            $costoTotal = 0;

            foreach ($salida->detalles as $detalle) {
                $producto = Producto::lockForUpdate()->find($detalle->producto_id);
                if (! $producto || $producto->tipo !== 'BIEN') {
                    continue;
                }

                $sucursalId = $salida->sucursal_id;
                $cantidadSalida = (float) $detalle->cantidad;
                $costoUnitario = (float) $detalle->costo_unitario;

                // 1. Descontar stock de la sucursal
                $pivot = $producto->sucursales()->where('sucursal_id', $sucursalId)->first();
                if ($pivot) {
                    $stockActual = max(0, (float) $pivot->pivot->stock_actual - $cantidadSalida);
                    $producto->sucursales()->updateExistingPivot($sucursalId, [
                        'stock_actual' => $stockActual,
                    ]);
                }

                // 2. Si maneja lotes, descontar del lote específico o FIFO
                if ($producto->maneja_lotes) {
                    if ($detalle->lote_id) {
                        $lote = Lote::lockForUpdate()->find($detalle->lote_id);
                        if ($lote) {
                            $lote->decrement('cantidad_actual', min($lote->cantidad_actual, $cantidadSalida));
                        }
                    } else {
                        $lotes = Lote::where('producto_id', $producto->id)
                            ->where('sucursal_id', $sucursalId)
                            ->where('cantidad_actual', '>', 0)
                            ->orderBy('created_at', 'asc')
                            ->get();

                        $pendiente = $cantidadSalida;
                        foreach ($lotes as $lote) {
                            if ($pendiente <= 0) break;
                            $aDescontar = min($lote->cantidad_actual, $pendiente);
                            $lote->decrement('cantidad_actual', $aDescontar);
                            $pendiente -= $aDescontar;
                        }
                    }
                }

                $subtotal = round($cantidadSalida * $costoUnitario, 2);
                $detalle->update(['subtotal' => $subtotal]);
                $costoTotal += $subtotal;
            }

            $salida->update(['costo_total' => round($costoTotal, 2)]);
        });
    }
}