<?php

namespace App\Filament\Widgets;

use App\Models\Gasto;
use App\Models\Producto;
use App\Models\Venta;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class ReportesDetalleWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static string $view = 'filament.widgets.reportes-detalle-widget';

    public static function canView(): bool
    {
        return true;
    }

    protected function getViewData(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;
        $sucursalId = $this->filters['sucursal_id'] ?? null;

        // Solo consideramos ventas completadas (excluyendo anuladas)
        $ventasQuery = Venta::query()->where('estado', 'COMPLETADA');
        $gastosQuery = class_exists(Gasto::class) ? Gasto::query() : null;

        if ($startDate) {
            $ventasQuery->whereDate('fecha_venta', '>=', $startDate);
            $gastosQuery?->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $ventasQuery->whereDate('fecha_venta', '<=', $endDate);
            $gastosQuery?->whereDate('created_at', '<=', $endDate);
        }
        if ($sucursalId) {
            $ventasQuery->where('sucursal_id', $sucursalId);
            $gastosQuery?->where('sucursal_id', $sucursalId);
        }

        // 1. Ingresos brutos recaudados / facturados
        $ingresosTotales = (float) (clone $ventasQuery)->sum('total');

        // 2. Impuestos determinados (SAT)
        $impuestosTotales = (float) (clone $ventasQuery)->sum('impuesto');

        // 3. Costo real de los productos vendidos (Costo de Ventas)
        $costoProductosVendidos = (float) (clone $ventasQuery)
            ->join('detalle_ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->selectRaw('SUM(detalle_ventas.cantidad * detalle_ventas.costo_unitario_historico) as costo_total')
            ->value('costo_total') ?? 0.0;

        // 4. Gastos operativos del negocio (si aplica el módulo de Gastos)
        $gastosOperativos = $gastosQuery ? (float) (clone $gastosQuery)->sum('monto') : 0.00;

        // 5. Utilidad Neta Real: Total Venta - Impuestos - Costo de Mercadería - Gastos Operativos
        $utilidad = ($ingresosTotales - $impuestosTotales - $costoProductosVendidos) - $gastosOperativos;

        $conteoVentas = (clone $ventasQuery)->count();

        $productosPopulares = Producto::where('activo', true)
            ->latest('id')
            ->take(5)
            ->get();

        $transacciones = (clone $ventasQuery)
            ->latest('id')
            ->take(5)
            ->get();

        return [
            'ingresos' => $ingresosTotales,
            'gastos' => $gastosOperativos,
            'costoProductos' => $costoProductosVendidos,
            'impuestos' => $impuestosTotales,
            'utilidad' => $utilidad,
            'conteoVentas' => $conteoVentas,
            'productosPopulares' => $productosPopulares,
            'transacciones' => $transacciones,
        ];
    }
}