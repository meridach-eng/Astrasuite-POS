<?php

namespace App\Filament\Resources\CompraResource\Pages;

use App\Filament\Resources\CompraResource;
use App\Models\Lote;
use App\Models\Producto;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateCompra extends CreateRecord
{
    protected static string $resource = CompraResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $compraId = $this->record->id;

        DB::transaction(function () use ($compraId) {
            // 1. Obtener los detalles recién guardados directamente de la BD
            $detalles = DB::table('detalle_compras')->where('compra_id', $compraId)->get();

            $subtotal = 0;
            foreach ($detalles as $det) {
                $linea = round((float) $det->cantidad * (float) $det->precio_unitario, 2);
                DB::table('detalle_compras')->where('id', $det->id)->update(['subtotal' => $linea]);
                $subtotal += $linea;
            }

            $compra = DB::table('compras')->where('id', $compraId)->first();
            $descuento = (float) ($compra->descuento ?? 0);
            $granTotal = max(0, round($subtotal - $descuento, 2));

            // 2. Sumar pagos registrados
            $pagado = (float) DB::table('pago_compras')->where('compra_id', $compraId)->sum('monto');
            $pagado = round($pagado, 2);
            $saldoPendiente = max(0, round($granTotal - $pagado, 2));

            $estadoPago = 'PENDIENTE';
            if ($pagado >= $granTotal && $granTotal > 0) {
                $estadoPago = 'PAGADO';
            } elseif ($pagado > 0 && $pagado < $granTotal) {
                $estadoPago = 'PARCIAL';
            }

            // 3. Grabar los totales reales calculados en la tabla compras
            DB::table('compras')->where('id', $compraId)->update([
                'subtotal' => round($subtotal, 2),
                'total' => $granTotal,
                'monto_pagado' => $pagado,
                'saldo_pendiente' => $saldoPendiente,
                'estado_pago' => $estadoPago,
            ]);

            // 4. Afectar Inventario, Lotes y AVCO si la compra fue RECIBIDA
            if ($compra->estado_recepcion === 'RECIBIDO') {
                foreach ($detalles as $det) {
                    $producto = Producto::lockForUpdate()->find($det->producto_id);

                    if (! $producto || $producto->tipo !== 'BIEN') {
                        continue;
                    }

                    $sucursalId = $compra->sucursal_id;
                    $cantidadComprada = (float) $det->cantidad;
                    $costoUnitarioCompra = (float) $det->precio_unitario;

                    $stockGlobalPrevio = (float) $producto->sucursales()->sum('stock_actual');
                    $costoPrevio = (float) $producto->precio_compra;

                    // Incrementar stock en la sucursal
                    $pivot = $producto->sucursales()->where('sucursal_id', $sucursalId)->first();
                    if ($pivot) {
                        $producto->sucursales()->updateExistingPivot($sucursalId, [
                            'stock_actual' => (float) $pivot->pivot->stock_actual + $cantidadComprada,
                        ]);
                    } else {
                        $producto->sucursales()->attach($sucursalId, [
                            'stock_actual' => $cantidadComprada,
                            'stock_minimo_sucursal' => $producto->stock_minimo,
                        ]);
                    }

                    // Recálculo del costo promedio ponderado (AVCO)
                    $nuevoStockGlobal = $stockGlobalPrevio + $cantidadComprada;
                    if ($nuevoStockGlobal > 0) {
                        $nuevoCostoAVCO = (($stockGlobalPrevio * $costoPrevio) + ($cantidadComprada * $costoUnitarioCompra)) / $nuevoStockGlobal;
                        $margen = (float) $producto->margen_utilidad;
                        $nuevoPrecioVenta = round($nuevoCostoAVCO + ($nuevoCostoAVCO * ($margen / 100)), 2);

                        $producto->update([
                            'precio_compra' => round($nuevoCostoAVCO, 4),
                            'precio_venta' => $nuevoPrecioVenta,
                        ]);
                    }

                    // Crear lote PEPS si aplica
                    if ($producto->maneja_lotes && $det->numero_lote) {
                        Lote::create([
                            'producto_id' => $producto->id,
                            'sucursal_id' => $sucursalId,
                            'compra_id' => $compraId,
                            'numero_lote' => $det->numero_lote,
                            'fecha_vencimiento' => $det->fecha_vencimiento,
                            'cantidad_inicial' => $cantidadComprada,
                            'cantidad_actual' => $cantidadComprada,
                            'costo_unitario' => $costoUnitarioCompra,
                            'activo' => true,
                        ]);
                    }
                }
            }
        });
    }
}