<?php

namespace App\Filament\Widgets;

use App\Models\CuotaCobro;
use App\Models\VentaCuota;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CuentasPorCobrarStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return true;
    }

    protected function getStats(): array
    {
        // 1. Cartera total pendiente por cobrar de todos los contratos activos
        $totalCartera = (float) VentaCuota::where('estado', 'ACTIVO')->sum('saldo_pendiente');

        // 2. Cuotas vencidas (Mora activa)
        $cuotasVencidasQuery = CuotaCobro::where('estado', '!=', 'PAGADO')
            ->where('estado', '!=', 'ANULADO')
            ->whereDate('fecha_vencimiento', '<', now()->toDateString());

        $totalMontoVencido = (float) (clone $cuotasVencidasQuery)->sum('saldo_pendiente');
        $conteoVencidas = (clone $cuotasVencidasQuery)->count();

        // 3. Cuotas que vencen en los próximos 15 días (Por cobrar en el corto plazo)
        $cuotasPorVencerQuery = CuotaCobro::where('estado', '!=', 'PAGADO')
            ->where('estado', '!=', 'ANULADO')
            ->whereBetween('fecha_vencimiento', [
                now()->toDateString(),
                now()->addDays(15)->toDateString(),
            ]);

        $totalPorVencer = (float) (clone $cuotasPorVencerQuery)->sum('saldo_pendiente');
        $conteoPorVencer = (clone $cuotasPorVencerQuery)->count();

        return [
            Stat::make('Cartera Total por Cobrar', 'Q' . number_format($totalCartera, 2))
                ->description('Saldo pendiente en contratos de crédito activos')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Por Cobrar (Próximos 15 Días)', 'Q' . number_format($totalPorVencer, 2))
                ->description("{$conteoPorVencer} cuota(s) programadas a vencer pronto")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),

            Stat::make('Cuotas Vencidas (En Mora)', 'Q' . number_format($totalMontoVencido, 2))
                ->description("{$conteoVencidas} cuota(s) vencidas sin liquidar")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($conteoVencidas > 0 ? 'danger' : 'success'),
        ];
    }
}