<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentaResource\Pages;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\FelplexService;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VentaResource extends Resource
{
    protected static ?string $model = Venta::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Ventas / POS';

    protected static ?string $navigationLabel = 'Historial de Ventas';

    protected static ?string $modelLabel = 'Venta';

    protected static ?string $pluralModelLabel = 'Ventas';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha_venta')
                    ->label('Fecha / Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('numero_ticket')
                    ->label('No. Ticket')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Venta $record) => 'Doc: ' . ($record->cliente->numero_documento ?? $record->cliente->nit ?? 'CF')),

                Tables\Columns\TextColumn::make('tipo_venta')
                    ->label('Modalidad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CONTADO' => 'success',
                        'CREDITO' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->color(fn (Venta $record): ?string => ($record->tipo_venta === 'CREDITO' && $record->saldo_pendiente > 0 && $record->fecha_vencimiento?->isPast()) ? 'danger' : null)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cajero')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('GTQ')
                    ->sortable()
                    ->weight('black'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'COMPLETADA' => 'success',
                        'PENDIENTE' => 'warning',
                        'ANULADA' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('estado_pago')
                    ->label('Pago')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PAGADO' => 'success',
                        'PARCIAL' => 'warning',
                        'CREDITO' => 'danger',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_venta')
                    ->label('Tipo de Venta')
                    ->options([
                        'CONTADO' => 'Contado',
                        'CREDITO' => 'Crédito',
                    ]),
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'COMPLETADA' => 'Completada',
                        'PENDIENTE' => 'Pendiente',
                        'ANULADA' => 'Anulada',
                    ]),
                Tables\Filters\SelectFilter::make('estado_pago')
                    ->options([
                        'PAGADO' => 'Pagado',
                        'PARCIAL' => 'Pago Parcial',
                        'CREDITO' => 'Al Crédito',
                    ]),
                Tables\Filters\SelectFilter::make('sucursal_id')
                    ->relationship('sucursal', 'nombre')
                    ->label('Sucursal'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('imprimir_ticket_simple')
                    ->label('Ticket')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (Venta $record): string => route('pos.ticket.imprimir', $record->id))
                    ->openUrlInNewTab()
                    ->visible(fn (Venta $record): bool => blank($record->fel_uuid)),

                Tables\Actions\Action::make('imprimir_ticket_sat')
                    ->label('Ticket SAT')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->url(fn (Venta $record): ?string => $record->fel_uuid ? "https://felplex-gt.stage.plex.lat/text/{$record->fel_uuid}" : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Venta $record): bool => filled($record->fel_uuid)),

                /*Tables\Actions\Action::make('ver_xml_fel')
                    ->label('XML')
                    ->icon('heroicon-o-code-bracket')
                    ->color('gray')
                    ->url(fn (Venta $record): ?string => $record->fel_uuid ? "https://felplex-gt.stage.plex.lat/xml/{$record->fel_uuid}" : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Venta $record): bool => filled($record->fel_uuid)),  */

                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Venta $record) => "Anular Venta {$record->numero_ticket}")
                    ->modalDescription('Esta acción anulará el DTE ante la SAT, devolverá las existencias a la sucursal, restablecerá los lotes consumidos y restará la deuda al cliente si fue al crédito.')
                    ->visible(fn (Venta $record) => $record->estado !== 'ANULADA')
                    ->form([
                        Forms\Components\Textarea::make('motivo_anulacion')
                            ->label('Razón de Anulación')
                            ->required()
                            ->default('Devolución')
                            ->placeholder('Ej. Devolución / Error en datos del receptor'),
                    ])
                    ->action(function (Venta $record, array $data) {
                        if (filled($record->fel_uuid)) {
                            $felService = new FelplexService();
                            $resultadoFel = $felService->anularFactura(
                                $record->fel_uuid,
                                $data['motivo_anulacion']
                            );

                            if (!$resultadoFel['success']) {
                                $errorMsg = is_array($resultadoFel['error']) ? json_encode($resultadoFel['error']) : $resultadoFel['error'];
                                Notification::make()
                                    ->title('Error al anular en FELplex')
                                    ->body($errorMsg)
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        DB::transaction(function () use ($record, $data) {
                            foreach ($record->detalles as $detalle) {
                                $producto = Producto::lockForUpdate()->find($detalle->producto_id);

                                if ($producto && $producto->tipo === 'BIEN') {
                                    $pivot = $producto->sucursales()->where('sucursal_id', $record->sucursal_id)->first();
                                    if ($pivot) {
                                        $nuevoStock = (float) $pivot->pivot->stock_actual + (float) $detalle->cantidad;
                                        $producto->sucursales()->updateExistingPivot($record->sucursal_id, [
                                            'stock_actual' => $nuevoStock,
                                        ]);
                                    }

                                    if ($detalle->lote_id) {
                                        $lote = Lote::lockForUpdate()->find($detalle->lote_id);
                                        if ($lote) {
                                            $nuevaCantidad = (float) $lote->cantidad_actual + (float) $detalle->cantidad;
                                            $lote->update([
                                                'cantidad_actual' => $nuevaCantidad,
                                                'activo' => true,
                                            ]);
                                        }
                                    }
                                }
                            }

                            if ((float) $record->saldo_pendiente > 0 && Schema::hasColumn('clientes', 'saldo_deudor')) {
                                $cliente = $record->cliente;
                                if ($cliente) {
                                    $nuevoSaldoDeudor = max(0, (float) $cliente->saldo_deudor - (float) $record->saldo_pendiente);
                                    $cliente->update(['saldo_deudor' => $nuevoSaldoDeudor]);
                                }
                            }

                            $record->update([
                                'estado' => 'ANULADA',
                                'saldo_pendiente' => 0,
                                'motivo_anulacion' => $data['motivo_anulacion'],
                                'anulado_at' => now(),
                            ]);
                        });

                        Notification::make()
                            ->title('Venta y DTE Anulados')
                            ->body('La factura electrónica fue anulada con éxito en la SAT y el inventario local fue restaurado.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make('Información del Comprobante')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('numero_ticket')
                                    ->label('No. Ticket')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('fecha_venta')
                                    ->label('Fecha y Hora')
                                    ->dateTime('d/m/Y H:i:s'),
                                Infolists\Components\TextEntry::make('tipo_venta')
                                    ->label('Modalidad')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'CONTADO' => 'success',
                                        'CREDITO' => 'info',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('estado')
                                    ->label('Estado Venta')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'COMPLETADA' => 'success',
                                        'PENDIENTE' => 'warning',
                                        'ANULADA' => 'danger',
                                    }),
                                Infolists\Components\TextEntry::make('fecha_vencimiento')
                                    ->label('Fecha Vencimiento')
                                    ->date('d/m/Y')
                                    ->placeholder('-')
                                    ->visible(fn (Venta $record) => $record->tipo_venta === 'CREDITO'),
                                Infolists\Components\TextEntry::make('cliente.nombre')
                                    ->label('Cliente'),
                                Infolists\Components\TextEntry::make('cliente_doc')
                                    ->label('Documento / NIT')
                                    ->state(fn (Venta $record) => $record->cliente->numero_documento ?? $record->cliente->nit ?? 'CF'),
                                Infolists\Components\TextEntry::make('sucursal.nombre')
                                    ->label('Sucursal'),
                                Infolists\Components\TextEntry::make('sesionCaja.caja.nombre')
                                    ->label('Caja Registradora'),
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Cajero'),
                                Infolists\Components\TextEntry::make('fel_uuid')
                                    ->label('UUID FELplex')
                                    ->placeholder('No certificado')
                                    ->columnSpan(2),
                            ]),

                        Infolists\Components\Section::make('Artículos Vendidos')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('detalles')
                                    ->label('')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('producto.nombre')
                                            ->label('Producto')
                                            ->columnSpan(4),
                                        Infolists\Components\TextEntry::make('lote.numero_lote')
                                            ->label('Lote Consumido')
                                            ->placeholder('-')
                                            ->columnSpan(2),
                                        Infolists\Components\TextEntry::make('cantidad')
                                            ->label('Cant.')
                                            ->columnSpan(1),
                                        Infolists\Components\TextEntry::make('precio_unitario')
                                            ->label('Precio')
                                            ->money('GTQ')
                                            ->columnSpan(2),
                                        Infolists\Components\TextEntry::make('subtotal')
                                            ->label('Subtotal')
                                            ->money('GTQ')
                                            ->weight('bold')
                                            ->columnSpan(3),
                                    ])
                                    ->columns(12),
                            ]),

                        Infolists\Components\Section::make('Auditoría de Anulación')
                            ->visible(fn (Venta $record) => $record->estado === 'ANULADA')
                            ->columns(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('motivo_anulacion')
                                    ->label('Razón de Anulación')
                                    ->color('danger'),
                                Infolists\Components\TextEntry::make('anulado_at')
                                    ->label('Fecha de Anulación')
                                    ->dateTime('d/m/Y H:i:s'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make('Resumen Financiero')
                            ->schema([
                                Infolists\Components\TextEntry::make('total')
                                    ->label('Gran Total')
                                    ->money('GTQ')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('black'),
                                Infolists\Components\TextEntry::make('monto_pagado')
                                    ->label('Monto Pagado')
                                    ->money('GTQ')
                                    ->color('success')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('saldo_pendiente')
                                    ->label('Saldo Pendiente')
                                    ->money('GTQ')
                                    ->color(fn (Venta $record) => $record->saldo_pendiente > 0 ? 'danger' : 'gray')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('estado_pago')
                                    ->label('Estado de Pago')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'PAGADO' => 'success',
                                        'PARCIAL' => 'warning',
                                        'CREDITO' => 'danger',
                                    }),
                            ]),

                        Infolists\Components\Section::make('Métodos de Pago')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('pagos')
                                    ->label('')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('metodo_pago')
                                            ->label('Método')
                                            ->badge()
                                            ->color('gray'),
                                        Infolists\Components\TextEntry::make('monto')
                                            ->label('Monto')
                                            ->money('GTQ')
                                            ->weight('bold'),
                                        Infolists\Components\TextEntry::make('referencia_pago')
                                            ->label('Ref.')
                                            ->placeholder('-'),
                                    ])
                                    ->columns(3),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentas::route('/'),
            'view' => Pages\ViewVenta::route('/{record}'),
        ];
    }
}