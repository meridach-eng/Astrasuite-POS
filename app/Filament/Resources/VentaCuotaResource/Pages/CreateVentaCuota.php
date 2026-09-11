<?php

namespace App\Filament\Resources\VentaCuotaResource\Pages;

use App\Filament\Resources\VentaCuotaResource;
use App\Models\Lote;
use App\Models\Producto;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateVentaCuota extends CreateRecord
{
    protected static string $resource = VentaCuotaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asignamos la sucursal activa de la sesión o del usuario
        $sucursalId = session('sucursal_activa_id') ?? auth()->user()->sucursal_id ?? 1;
        
        $data['sucursal_id'] = $sucursalId;
        $data['user_id'] = auth()->id();
        $data['total_pagado'] = $data['enganche'] ?? 0;
        $data['saldo_pendiente'] = $data['saldo_financiar'] ?? 0;
        $data['estado'] = ((float) $data['saldo_pendiente'] <= 0) ? 'LIQUIDADO' : 'ACTIVO';

        return $data;
    }

    protected function beforeCreate(): void
    {
        $sucursalId = session('sucursal_activa_id') ?? auth()->user()->sucursal_id ?? 1;
        $items = $this->data['detalles'] ?? [];

        // 1. Validación previa de existencia de stock antes de crear el contrato
        foreach ($items as $item) {
            $producto = Producto::find($item['producto_id']);
            if (! $producto || $producto->tipo !== 'BIEN') {
                continue;
            }

            $cantidadSolicitada = (float) ($item['cantidad'] ?? 1);
            $stockDisponible = $producto->stockEnSucursal($sucursalId);

            if ($stockDisponible < $cantidadSolicitada) {
                Notification::make()
                    ->title('Stock Insuficiente')
                    ->body("El producto {$producto->nombre} solo tiene {$stockDisponible} unidades disponibles en esta sucursal.")
                    ->danger()
                    ->send();

                $this->halt(); // Detiene la creación inmediatamente
            }
        }
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $sucursalId = $record->sucursal_id;
        $items = $this->data['detalles'] ?? [];

        DB::transaction(function () use ($record, $sucursalId, $items) {
            // 2. Descontar existencias físicas en la sucursal y consumir Lotes PEPS/FIFO
            foreach ($items as $item) {
                $productoId = $item['producto_id'];
                $cantidadADescontar = (float) ($item['cantidad'] ?? 1);

                $producto = Producto::lockForUpdate()->find($productoId);
                if (! $producto || $producto->tipo !== 'BIEN') {
                    continue;
                }

                // Descuento en la tabla pivote de sucursal
                $pivot = $producto->sucursales()->where('sucursal_id', $sucursalId)->first();
                if ($pivot) {
                    $nuevoStock = max(0, (float) $pivot->pivot->stock_actual - $cantidadADescontar);
                    $producto->sucursales()->updateExistingPivot($sucursalId, [
                        'stock_actual' => $nuevoStock,
                    ]);
                }

                // Descuento en Lotes (si el producto maneja lotes)
                if ($producto->maneja_lotes) {
                    $lotes = Lote::where('producto_id', $producto->id)
                        ->where('sucursal_id', $sucursalId)
                        ->where('activo', true)
                        ->where('cantidad_actual', '>', 0)
                        ->orderBy('fecha_vencimiento', 'asc')
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();

                    $restantePorDescontar = $cantidadADescontar;
                    foreach ($lotes as $lote) {
                        if ($restantePorDescontar <= 0) break;

                        $consumo = min($restantePorDescontar, (float) $lote->cantidad_actual);
                        $lote->update([
                            'cantidad_actual' => (float) $lote->cantidad_actual - $consumo,
                            'activo' => ((float) $lote->cantidad_actual - $consumo) > 0,
                        ]);

                        $restantePorDescontar -= $consumo;
                    }
                }
            }

            // 3. Aumentar el saldo deudor del cliente (si existe la columna)
            if ($record->cliente && Schema::hasColumn('clientes', 'saldo_deudor')) {
                $record->cliente->increment('saldo_deudor', $record->saldo_pendiente);
            }
        });
    }
}