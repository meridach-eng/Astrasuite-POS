<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CuotaCobroResource\Pages;
use App\Models\CuotaCobro;
use App\Models\PagoCuotaCobro;
use App\Models\SesionCaja;
use Filament\Forms;
use Filament\Infolists;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CuotaCobroResource extends Resource
{
    protected static ?string $model = CuotaCobro::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Ventas / POS';

    protected static ?string $navigationLabel = 'Cobro de Cuotas';

    protected static ?string $modelLabel = 'Cobro de Cuota';

    protected static ?string $pluralModelLabel = 'Cobro de Cuotas';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['ventaCuota', 'cliente', 'pagos.user'])
            ->where('estado', '!=', 'ANULADO');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_cuota')
                    ->label('N°')
                    ->formatStateUsing(fn ($state) => "Cuota #{$state}")
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('ventaCuota.numero_referencia')
                    ->label('Referencia')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->description(fn (CuotaCobro $record) => 'NIT/Doc: ' . ($record->cliente->numero_documento ?? 'CF')),

                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Fecha Cuota')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (CuotaCobro $record): ?string => ($record->estado !== 'PAGADO' && $record->fecha_vencimiento->isPast()) ? 'danger' : null)
                    ->description(fn (CuotaCobro $record) => ($record->estado !== 'PAGADO' && $record->fecha_vencimiento->isPast()) ? '¡Vencida!' : null),

                Tables\Columns\TextColumn::make('monto_cuota')
                    ->label('Cuota Monto')
                    ->money('GTQ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto_pagado')
                    ->label('Pagado Monto')
                    ->money('GTQ')
                    ->color('success'),

                Tables\Columns\TextColumn::make('saldo_pendiente')
                    ->label('Restante Monto')
                    ->money('GTQ')
                    ->color('danger')
                    ->weight('black')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PAGADO' => 'success',
                        'PARCIAL' => 'warning',
                        'PENDIENTE' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('fecha_vencimiento', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('venta_cuota_id')
                    ->label('Contrato / Referencia')
                    ->relationship('ventaCuota', 'numero_referencia')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'PENDIENTE' => 'Pendiente',
                        'PARCIAL' => 'Parcial',
                        'PAGADO' => 'Pagado',
                    ]),

                Tables\Filters\Filter::make('vencidas')
                    ->label('Solo Vencidas')
                    ->query(fn (Builder $query) => $query->where('fecha_vencimiento', '<', now()->toDateString())->where('estado', '!=', 'PAGADO')),

                Tables\Filters\SelectFilter::make('cliente_id')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Cliente'),
            ])
            ->actions([
                // ACCIÓN 1: COBRAR / ABONAR CON BOTÓN DE IMPRESIÓN DIRECTA
                Tables\Actions\Action::make('cobrar')
                    ->label('Cobrar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (CuotaCobro $record) => $record->saldo_pendiente > 0 && $record->estado !== 'ANULADO')
                    ->modalHeading(fn (CuotaCobro $record) => "Cobrar Cuota #{$record->numero_cuota} - {$record->ventaCuota->numero_referencia}")
                    ->modalDescription(fn (CuotaCobro $record) => "Cliente: {$record->cliente->nombre} | Saldo pendiente: Q" . number_format($record->saldo_pendiente, 2))
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('monto')
                                    ->label('Monto a Cobrar')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->required()
                                    ->default(fn (CuotaCobro $record) => $record->saldo_pendiente)
                                    ->maxValue(fn (CuotaCobro $record) => (float) $record->saldo_pendiente)
                                    ->helperText('Puedes cobrar la cuota completa o un abono parcial.'),

                                Forms\Components\DatePicker::make('fecha_pago')
                                    ->label('Fecha del Cobro')
                                    ->required()
                                    ->default(now()->toDateString()),

                                Forms\Components\Select::make('metodo_pago')
                                    ->label('Método de Pago')
                                    ->options([
                                        'EFECTIVO' => 'Efectivo',
                                        'TRANSFERENCIA' => 'Transferencia Bancaria',
                                        'TARJETA' => 'Tarjeta POS',
                                        'CHEQUE' => 'Cheque',
                                    ])
                                    ->default('EFECTIVO')
                                    ->required(),

                                Forms\Components\TextInput::make('referencia')
                                    ->label('No. Boleta / Voucher')
                                    ->placeholder('Opcional'),

                                Forms\Components\Textarea::make('notas')
                                    ->label('Observaciones')
                                    ->columnSpanFull()
                                    ->placeholder('Notas adicionales del pago...'),
                            ]),
                    ])
                    ->action(function (CuotaCobro $record, array $data) {
                        $pagoRegistrado = DB::transaction(function () use ($record, $data) {
                            $montoCobrado = (float) $data['monto'];
                            $sesionActiva = SesionCaja::where('user_id', auth()->id())
                                ->where('estado', 'ABIERTA')
                                ->latest('fecha_apertura')
                                ->first();

                            $pago = PagoCuotaCobro::create([
                                'cuota_cobro_id' => $record->id,
                                'venta_cuota_id' => $record->venta_cuota_id,
                                'cliente_id' => $record->cliente_id,
                                'user_id' => auth()->id(),
                                'sesion_caja_id' => $sesionActiva?->id,
                                'monto' => $montoCobrado,
                                'metodo_pago' => $data['metodo_pago'],
                                'fecha_pago' => $data['fecha_pago'],
                                'referencia' => $data['referencia'] ?? null,
                                'notas' => $data['notas'] ?? null,
                            ]);

                            $nuevoPagadoCuota = round((float) $record->monto_pagado + $montoCobrado, 2);
                            $nuevoSaldoCuota = max(0, round((float) $record->saldo_pendiente - $montoCobrado, 2));
                            $estadoCuota = $nuevoSaldoCuota <= 0 ? 'PAGADO' : 'PARCIAL';

                            $record->update([
                                'monto_pagado' => $nuevoPagadoCuota,
                                'saldo_pendiente' => $nuevoSaldoCuota,
                                'fecha_ultimo_pago' => $data['fecha_pago'],
                                'estado' => $estadoCuota,
                            ]);

                            $ventaCuota = $record->ventaCuota;
                            if ($ventaCuota) {
                                $nuevoTotalPagado = round((float) $ventaCuota->total_pagado + $montoCobrado, 2);
                                $nuevoSaldoContrato = max(0, round((float) $ventaCuota->saldo_pendiente - $montoCobrado, 2));
                                $estadoContrato = $nuevoSaldoContrato <= 0 ? 'LIQUIDADO' : 'ACTIVO';

                                $ventaCuota->update([
                                    'total_pagado' => $nuevoTotalPagado,
                                    'saldo_pendiente' => $nuevoSaldoContrato,
                                    'estado' => $estadoContrato,
                                ]);
                            }

                            if ($record->cliente && Schema::hasColumn('clientes', 'saldo_deudor')) {
                                $record->cliente->decrement('saldo_deudor', $montoCobrado);
                            }

                            return $pago;
                        });

                        Notification::make()
                            ->title('Cobro Registrado')
                            ->body('El pago se aplicó a la cuota exitosamente.')
                            ->success()
                            ->actions([
                                NotificationAction::make('imprimir')
                                    ->label('Imprimir Recibo')
                                    ->icon('heroicon-o-printer')
                                    ->url(route('cuotas.recibo.imprimir', $pagoRegistrado->id), shouldOpenInNewTab: true)
                                    ->button(),
                            ])
                            ->persistent()
                            ->send();
                    }),

                // ACCIÓN 2: CONSULTAR ABONOS HISTÓRICOS Y REIMPRIMIR
                Tables\Actions\Action::make('historial_pagos')
                    ->label('Abonos')
                    ->icon('heroicon-o-receipt-percent')
                    ->color('info')
                    ->modalHeading(fn (CuotaCobro $record) => "Historial de Abonos - Cuota #{$record->numero_cuota}")
                    ->modalDescription(fn (CuotaCobro $record) => "Monto cuota: Q" . number_format($record->monto_cuota, 2) . " | Pagado: Q" . number_format($record->monto_pagado, 2) . " | Pendiente: Q" . number_format($record->saldo_pendiente, 2))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->infolist(fn (CuotaCobro $record) => [
                        Infolists\Components\RepeatableEntry::make('pagos')
                            ->label('Abonos Registrados')
                            ->schema([
                                Infolists\Components\TextEntry::make('fecha_pago')
                                    ->label('Fecha')
                                    ->date('d/m/Y')
                                    ->columnSpan(2),

                                Infolists\Components\TextEntry::make('metodo_pago')
                                    ->label('Método')
                                    ->badge()
                                    ->columnSpan(2),

                                Infolists\Components\TextEntry::make('monto')
                                    ->label('Abono')
                                    ->money('GTQ')
                                    ->weight('bold')
                                    ->color('success')
                                    ->columnSpan(2),

                                Infolists\Components\TextEntry::make('referencia')
                                    ->label('Boleta/Ref')
                                    ->placeholder('-')
                                    ->columnSpan(2),

                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Cajero')
                                    ->columnSpan(2),

                                Infolists\Components\TextEntry::make('id')
                                    ->label('Acción')
                                    ->formatStateUsing(fn () => 'Imprimir Recibo')
                                    ->icon('heroicon-o-printer')
                                    ->color('primary')
                                    ->url(fn ($record) => $record ? route('cuotas.recibo.imprimir', $record->id) : null, shouldOpenInNewTab: true)
                                    ->columnSpan(2),
                            ])
                            ->columns(12),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCuotaCobros::route('/'),
        ];
    }
}