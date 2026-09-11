<?php

namespace App\Filament\Widgets;

use App\Models\Venta;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class IngresosChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Reporte de ingresos';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $startDate = !empty($this->filters['startDate']) ? Carbon::parse($this->filters['startDate']) : now()->startOfYear();
        $endDate = !empty($this->filters['endDate']) ? Carbon::parse($this->filters['endDate']) : now();
        $sucursalId = $this->filters['sucursal_id'] ?? null;

        $labels = [];
        $values = [];

        $period = CarbonPeriod::create($startDate, '1 month', $endDate);

        foreach ($period as $date) {
            $labels[] = $date->translatedFormat('M');

            $query = Venta::query()
                ->whereYear('fecha_venta', $date->year)
                ->whereMonth('fecha_venta', $date->month);

            if ($sucursalId) {
                $query->where('sucursal_id', $sucursalId);
            }

            $values[] = (float) $query->sum('total');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => $values,
                    'fill' => true,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                    'borderWidth' => 2.5,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#059669',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'grid' => [
                        'borderDash' => [4, 4],
                        'color' => 'rgba(226, 232, 240, 0.6)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}