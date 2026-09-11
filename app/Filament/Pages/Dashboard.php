<?php

namespace App\Filament\Pages;

use App\Models\Sucursal;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Support\Enums\MaxWidth;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::Full;
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\EstadisticasOverview::class,
            \App\Filament\Widgets\CuentasPorCobrarStatsWidget::class,
            \App\Filament\Widgets\IngresosChart::class,
            \App\Filament\Widgets\ReportesDetalleWidget::class,
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Fecha desde')
                            ->default(now()->startOfYear()->toDateString()),

                        DatePicker::make('endDate')
                            ->label('Fecha hasta')
                            ->default(now()->toDateString()),

                        Select::make('sucursal_id')
                            ->label('Sucursal')
                            ->options(Sucursal::where('activo', true)->pluck('nombre', 'id'))
                            ->placeholder('Todas las sucursales')
                            ->default(session('sucursal_activa_id')),
                    ]),
            ]);
    }
}