<?php

namespace App\Filament\Widgets;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class EstadisticasOverview extends Widget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected static string $view = 'filament.widgets.estadisticas-overview';

    public static function canView(): bool
    {
        return true;
    }

    protected function getViewData(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;
        $sucursalId = $this->filters['sucursal_id'] ?? null;

        $query = Venta::query();

        if ($startDate) {
            $query->whereDate('fecha_venta', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('fecha_venta', '<=', $endDate);
        }
        if ($sucursalId) {
            $query->where('sucursal_id', $sucursalId);
        }

        return [
            'ventas' => (clone $query)->count(),
            'clientes' => Cliente::where('activo', true)->count(),
            'productos' => Producto::where('activo', true)->count(),
            'ingresos' => (clone $query)->sum('total'),
        ];
    }
}