<?php

namespace App\Filament\Pages;

use App\Models\Lote;
use App\Models\ProductoSucursal;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ReporteInventario extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Catálogo / Inventario';
    protected static ?string $navigationLabel = 'Alertas de Inventario';
    protected static ?string $title = 'Alertas de Stock y Vencimientos';
    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.reporte-inventario';

    public ?int $sucursalId = null;
    public int $diasVencimiento = 30; // Valor por defecto

    public function mount(): void
    {
        $this->sucursalId = session('sucursal_activa_id');
    }

    /**
     * Tabla 1: Productos con Bajo Stock
     */
    public function table(Table $table): Table
    {
        $sucId = $this->sucursalId;

        return $table
            ->query(
                ProductoSucursal::query()
                    ->with(['producto.categoria', 'sucursal'])
                    ->when($sucId, fn ($q) => $q->where('sucursal_id', $sucId))
                    ->whereColumn('stock_actual', '<=', 'stock_minimo_sucursal')
            )
            ->columns([
                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->badge()
                    ->color('info')
                    ->visible(! $sucId),

                Tables\Columns\TextColumn::make('producto.codigo_interno')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('stock_actual')
                    ->label('Stock Actual')
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_minimo_sucursal')
                    ->label('Stock Mínimo')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('producto.categoria.nombre')
                    ->label('Categoría')
                    ->sortable(),
            ])
            ->heading('Productos con Stock Bajo o Agotado')
            ->description('Artículos cuyo inventario actual está en o por debajo del mínimo establecido.');
    }

    /**
     * Obtiene la lista de lotes próximos a vencer basándose en los días seleccionados
     */
    public function getLotesProximosVencerProperty()
    {
        $sucId = $this->sucursalId;
        $fechaLimite = now()->addDays($this->diasVencimiento);

        return Lote::query()
            ->with(['producto', 'sucursal'])
            ->when($sucId, fn ($q) => $q->where('sucursal_id', $sucId))
            ->where('activo', true)
            ->where('cantidad_actual', '>', 0)
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '>', '2000-01-01')
            ->where('fecha_vencimiento', '<=', $fechaLimite)
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();
    }
}