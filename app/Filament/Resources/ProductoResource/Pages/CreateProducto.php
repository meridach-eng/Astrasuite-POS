<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use App\Models\Lote;
use App\Models\Sucursal;
use Filament\Resources\Pages\CreateRecord;

class CreateProducto extends CreateRecord
{
    protected static string $resource = ProductoResource::class;

    protected function afterCreate(): void
    {
        $producto = $this->record;
        $formData = $this->form->getState();

        if ($producto->tipo !== 'BIEN') {
            return;
        }

        $sucursales = Sucursal::where('activo', true)->get();

        foreach ($sucursales as $sucursal) {
            $stock = (float) ($formData["stock_sucursal_{$sucursal->id}"] ?? 0);

            $producto->sucursales()->attach($sucursal->id, [
                'stock_actual' => $stock,
                'stock_minimo_sucursal' => $producto->stock_minimo,
            ]);

            // Si maneja lotes y tiene existencias iniciales, crear lote de apertura PEPS
            if ($producto->maneja_lotes && $stock > 0) {
                Lote::create([
                    'producto_id' => $producto->id,
                    'sucursal_id' => $sucursal->id,
                    'numero_lote' => 'LOTE-INICIAL-' . now()->format('Ymd'),
                    'fecha_vencimiento' => now()->addMonths(12)->toDateString(),
                    'cantidad_inicial' => $stock,
                    'cantidad_actual' => $stock,
                    'costo_unitario' => $producto->precio_compra,
                    'activo' => true,
                ]);
            }
        }
    }
}