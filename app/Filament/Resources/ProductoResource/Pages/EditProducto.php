<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use App\Models\Sucursal;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProducto extends EditRecord
{
    protected static string $resource = ProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $producto = $this->record;

        // Cargar las existencias actuales de cada sucursal en modo solo lectura para consulta visual
        if ($producto->tipo === 'BIEN') {
            $sucursales = Sucursal::where('activo', true)->get();
            foreach ($sucursales as $sucursal) {
                $pivot = $producto->sucursales()->where('sucursal_id', $sucursal->id)->first();
                $data["stock_sucursal_{$sucursal->id}"] = $pivot ? $pivot->pivot->stock_actual : 0;
            }
        }

        return $data;
    }
}