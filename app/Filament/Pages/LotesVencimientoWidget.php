<?php

namespace App\Filament\Pages\Widgets;

use App\Models\Lote;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LotesVencimientoWidget extends BaseWidget
{
    protected static ?string $heading = 'Lotes Próximos a Vencer o Vencidos';

    public function table(Table $table): Table
    {
        $sucId = session('sucursal_activa_id');
        $fechaLimite = now()->addDays(30);

        return $table
            ->query(
                Lote::query()
                    ->with(['producto', 'sucursal'])
                    ->when($sucId, fn ($q) => $q->where('sucursal_id', $sucId))
                    ->where('activo', true)
                    ->where('cantidad_actual', '>', 0)
                    ->whereNotNull('fecha_vencimiento')
                    ->where('fecha_vencimiento', '>', '2000-01-01') // Evita fechas nulas o basura técnica
                    ->where('fecha_vencimiento', '<=', $fechaLimite)
            )
            ->columns([
                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->badge()
                    ->color('info')
                    ->visible(! $sucId),

                Tables\Columns\TextColumn::make('numero_lote')
                    ->label('No. Lote')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('cantidad_actual')
                    ->label('Cantidad en Lote')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->badge()
                    ->color(fn ($record) => optional($record->fecha_vencimiento)->isPast() ? 'danger' : 'warning')
                    ->description(fn ($record) => optional($record->fecha_vencimiento)->isPast() ? '¡VENCIDO!' : 'Próximo a vencer')
                    ->sortable(),
            ])
            ->description('Lotes activos con stock remanente que caducarán en los próximos 30 días o ya expiraron.');
    }
}