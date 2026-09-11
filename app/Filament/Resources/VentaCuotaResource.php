<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CuotaCobroResource;
use App\Filament\Resources\VentaCuotaResource\Pages;
use App\Models\Cliente;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\VentaCuota;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VentaCuotaResource extends Resource
{
    protected static ?string $model = VentaCuota::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Ventas / POS';

    protected static ?string $navigationLabel = 'Venta a Cuotas';

    protected static ?string $modelLabel = 'Venta a Cuotas';

    protected static ?string $pluralModelLabel = 'Ventas a Cuotas';

    protected static ?int $navigationSort = 3;
    
    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }
    
    // Regla de inmutabilidad: Ni editar ni borrar contratos creados
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                    ->schema([

                        // COLUMNA IZQUIERDA: INFORMACIÓN, PRODUCTOS Y CONDICIONES (7 COLS)
                        Forms\Components\Group::make()
                            ->columnSpan(['lg' => 7])
                            ->schema([
                                Forms\Components\Section::make('Información de la Venta')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DatePicker::make('fecha_inicio')
                                                    ->label('Fecha')
                                                    ->required()
                                                    ->default(now()->toDateString()),

                                                Forms\Components\TextInput::make('numero_referencia')
                                                    ->label('N° de referencia')
                                                    ->required()
                                                    ->default(fn () => 'INS-' . date('Y') . '-' . str_pad((VentaCuota::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT))
                                                    ->readOnly(),
                                            ]),

                                        Forms\Components\Select::make('cliente_id')
                                            ->label('Cliente')
                                            ->relationship('cliente', 'nombre')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->createOptionAction(function (Action $action) {
                                                return $action
                                                    ->icon('heroicon-m-plus')
                                                    ->tooltip('Registrar nuevo cliente');
                                            })
                                            ->createOptionForm([
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\Select::make('tipo_documento')
                                                            ->label('Tipo Documento')
                                                            ->options([
                                                                'NIT' => 'NIT',
                                                                'DPI' => 'DPI / CUI',
                                                                'PASAPORTE' => 'Pasaporte',
                                                            ])
                                                            ->default('NIT')
                                                            ->required(),

                                                        Forms\Components\TextInput::make('numero_documento')
                                                            ->label('No. Documento / NIT')
                                                            ->required()
                                                            ->placeholder('Ej. 10458291'),

                                                        Forms\Components\TextInput::make('nombre')
                                                            ->label('Nombre Completo / Razón Social')
                                                            ->required()
                                                            ->columnSpanFull(),

                                                        Forms\Components\TextInput::make('telefono')
                                                            ->label('Teléfono')
                                                            ->tel(),

                                                        Forms\Components\TextInput::make('direccion')
                                                            ->label('Dirección')
                                                            ->default('CIUDAD'),

                                                        Forms\Components\TextInput::make('dias_credito')
                                                            ->label('Días de Crédito')
                                                            ->numeric()
                                                            ->default(30)
                                                            ->helperText('Días habituales de plazo'),

                                                        Forms\Components\TextInput::make('limite_credito')
                                                            ->label('Límite de Crédito')
                                                            ->numeric()
                                                            ->prefix('Q')
                                                            ->default(5000)
                                                            ->helperText('Monto máximo autorizado'),
                                                    ]),
                                            ])
                                            ->createOptionModalHeading('Registrar Nuevo Cliente')
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if (! $state) return;
                                                $cli = Cliente::find($state);
                                                if ($cli && (int) ($cli->dias_credito ?? 0) > 0) {
                                                    $set('dias_intervalo', (int) $cli->dias_credito);
                                                }
                                            }),
                                    ]),

                                // REPEATER DE PRODUCTOS
                                Forms\Components\Section::make('Productos en la Venta')
                                    ->schema([
                                        Forms\Components\Repeater::make('detalles')
                                            ->relationship()
                                            ->schema([
                                                Forms\Components\Select::make('producto_id')
                                                    ->label('Producto')
                                                    ->relationship('producto', 'nombre')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        if (! $state) return;
                                                        $prod = Producto::find($state);
                                                        if ($prod) {
                                                            $set('precio_unitario', (float) $prod->precio_venta);
                                                            $cant = (float) ($get('cantidad') ?? 1);
                                                            $desc = (float) ($get('descuento') ?? 0);
                                                            $set('subtotal', max(0, round(($cant * (float) $prod->precio_venta) - $desc, 2)));
                                                        }
                                                        static::actualizarSubtotalGeneral($get, $set);
                                                    })
                                                    ->columnSpanFull(),

                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('cantidad')
                                                            ->label('Cantidad')
                                                            ->numeric()
                                                            ->default(1)
                                                            ->minValue(1)
                                                            ->required()
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                                                $cant = (float) ($get('cantidad') ?? 1);
                                                                $precio = (float) ($get('precio_unitario') ?? 0);
                                                                $desc = (float) ($get('descuento') ?? 0);
                                                                $set('subtotal', max(0, round(($cant * $precio) - $desc, 2)));
                                                                static::actualizarSubtotalGeneral($get, $set);
                                                            }),

                                                        Forms\Components\TextInput::make('precio_unitario')
                                                            ->label('Precio')
                                                            ->numeric()
                                                            ->prefix('Q')
                                                            ->required()
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                                                $cant = (float) ($get('cantidad') ?? 1);
                                                                $precio = (float) ($get('precio_unitario') ?? 0);
                                                                $desc = (float) ($get('descuento') ?? 0);
                                                                $set('subtotal', max(0, round(($cant * $precio) - $desc, 2)));
                                                                static::actualizarSubtotalGeneral($get, $set);
                                                            }),

                                                        Forms\Components\TextInput::make('subtotal')
                                                            ->label('Subtotal')
                                                            ->numeric()
                                                            ->prefix('Q')
                                                            ->readOnly()
                                                            ->extraInputAttributes(['style' => 'font-weight: 700; color: #0f172a;']),
                                                    ])
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(1)
                                            ->live()
                                            ->afterStateUpdated(fn (Get $get, Set $set) => static::actualizarSubtotalGeneral($get, $set))
                                            ->addActionLabel('Agregar otro producto'),
                                    ]),

                                // CONDICIONES DEL CRÉDITO
                                Forms\Components\Section::make('Condiciones del Crédito')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('precio_base')
                                                    ->label('Suma de Productos')
                                                    ->numeric()
                                                    ->prefix('Q')
                                                    ->readOnly()
                                                    ->extraInputAttributes(['style' => 'font-weight: 700;']),

                                                Forms\Components\TextInput::make('descuento')
                                                    ->label('Descuento General')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->prefix('Q')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularTotales($get, $set)),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('porcentaje_interes')
                                                    ->label('Interés Financiero %')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->suffix('%')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularTotales($get, $set)),

                                                Forms\Components\TextInput::make('envio_otros')
                                                    ->label('Envío u otros')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->prefix('Q')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularTotales($get, $set)),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('total')
                                                    ->label('Total General')
                                                    ->numeric()
                                                    ->prefix('Q')
                                                    ->readOnly()
                                                    ->extraInputAttributes(['style' => 'font-weight: 900; color: #4F46E5;']),

                                                Forms\Components\TextInput::make('enganche')
                                                    ->label('Enganche Recibido')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->prefix('Q')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularTotales($get, $set)),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('saldo_financiar')
                                                    ->label('Saldo a Financiar')
                                                    ->numeric()
                                                    ->prefix('Q')
                                                    ->readOnly()
                                                    ->extraInputAttributes(['style' => 'font-weight: 900; color: #059669;']),

                                                Forms\Components\Select::make('metodo_pago_enganche')
                                                    ->label('Cuenta Enganche')
                                                    ->options([
                                                        'EFECTIVO' => 'Caja / Efectivo',
                                                        'TRANSFERENCIA' => 'Banco / Transferencia',
                                                        'TARJETA' => 'Tarjeta POS',
                                                        'SIN_ENGANCHE' => 'Sin Enganche',
                                                    ])
                                                    ->default('EFECTIVO'),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('numero_cuotas')
                                                    ->label('N° de cuotas')
                                                    ->numeric()
                                                    ->default(3)
                                                    ->minValue(1)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::generarTablaCuotas($get, $set)),

                                                Forms\Components\TextInput::make('dias_intervalo')
                                                    ->label('Intervalo de pago (Días)')
                                                    ->numeric()
                                                    ->default(30)
                                                    ->required()
                                                    ->suffixAction(
                                                        Action::make('generar')
                                                            ->label('Generar')
                                                            ->icon('heroicon-m-sparkles')
                                                            ->color('primary')
                                                            ->action(function (Get $get, Set $set) {
                                                                static::generarTablaCuotas($get, $set);
                                                                Notification::make()->title('Cuotas Calculadas')->success()->send();
                                                            })
                                                    ),
                                            ]),
                                    ]),
                            ]),

                        // COLUMNA DERECHA: CRONOGRAMA DE CUOTAS (5 COLS)
                        Forms\Components\Section::make('Cuotas Programadas')
                            ->description('Cronograma calculado automáticamente.')
                            ->columnSpan(['lg' => 5])
                            ->schema([
                                Forms\Components\Repeater::make('cuotas')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\TextInput::make('numero_cuota')
                                            ->label('N°')
                                            ->readOnly()
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('monto_cuota')
                                            ->label('Monto')
                                            ->prefix('Q')
                                            ->required()
                                            ->numeric()
                                            ->columnSpan(5),

                                        Forms\Components\DatePicker::make('fecha_vencimiento')
                                            ->label('Fecha Pago')
                                            ->required()
                                            ->columnSpan(5),

                                        Forms\Components\Hidden::make('saldo_pendiente')
                                            ->default(fn (Get $get) => (float) $get('monto_cuota')),

                                        Forms\Components\Hidden::make('estado')
                                            ->default('PENDIENTE'),

                                        Forms\Components\Hidden::make('cliente_id')
                                            ->default(fn (Get $get) => $get('../../cliente_id')),
                                    ])
                                    ->columns(12)
                                    ->addable(false)
                                    ->reorderable(false)
                                    ->deletable(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_referencia')
                    ->label('Referencia')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('detalles_count')
                    ->counts('detalles')
                    ->label('Artículos')
                    ->badge(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total Venta')
                    ->money('GTQ')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('enganche')
                    ->label('Enganche')
                    ->money('GTQ')
                    ->color('success'),

                Tables\Columns\TextColumn::make('saldo_pendiente')
                    ->label('Saldo Deudor')
                    ->money('GTQ')
                    ->color('danger')
                    ->weight('black'),

                Tables\Columns\TextColumn::make('numero_cuotas')
                    ->label('Plazo')
                    ->formatStateUsing(fn ($record) => "{$record->numero_cuotas} cuotas"),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ACTIVO' => 'warning',
                        'LIQUIDADO' => 'success',
                        'ANULADO' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'ACTIVO' => 'Activo',
                        'LIQUIDADO' => 'Liquidado',
                        'ANULADO' => 'Anulado',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                // ACCIÓN PARA DESCARGAR CONTRATO A4 PDF
                Tables\Actions\Action::make('pdf')
                    ->label('Contrato PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(fn (VentaCuota $record): string => route('ventas-cuotas.pdf', $record->id), shouldOpenInNewTab: true),

                // ACCIÓN DIRECTA PARA VER / COBRAR CUOTAS PRE-FILTRADAS
                Tables\Actions\Action::make('ver_cobros')
                    ->label('Cobros')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->url(fn (VentaCuota $record): string => CuotaCobroResource::getUrl('index', [
                        'tableFilters' => [
                            'venta_cuota_id' => [
                                'value' => $record->id,
                            ],
                        ],
                    ])),

                // ACCIÓN DE ANULACIÓN IDÉNTICA AL POS CON REVERSIÓN TOTAL
                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (VentaCuota $record) => "Anular Venta a Cuotas {$record->numero_referencia}")
                    ->modalDescription('Esta acción devolverá las existencias a la sucursal y restará el saldo pendiente de la deuda del cliente.')
                    ->visible(fn (VentaCuota $record) => $record->estado !== 'ANULADO')
                    ->form([
                        Forms\Components\Textarea::make('motivo_anulacion')
                            ->label('Motivo de Anulación')
                            ->required()
                            ->placeholder('Ej. Devolución de mercadería / Cancelación de contrato'),
                    ])
                    ->action(function (VentaCuota $record, array $data) {
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

                                    if ($producto->maneja_lotes) {
                                        $lote = Lote::where('producto_id', $producto->id)
                                            ->where('sucursal_id', $record->sucursal_id)
                                            ->latest('id')
                                            ->first();

                                        if ($lote) {
                                            $lote->increment('cantidad_actual', (float) $detalle->cantidad);
                                            $lote->update(['activo' => true]);
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

                            $record->cuotas()->where('estado', '!=', 'PAGADO')->update([
                                'estado' => 'ANULADO',
                                'saldo_pendiente' => 0,
                            ]);

                            $record->update([
                                'estado' => 'ANULADO',
                                'saldo_pendiente' => 0,
                                'motivo_anulacion' => $data['motivo_anulacion'],
                                'anulado_at' => now(),
                            ]);
                        });

                        Notification::make()
                            ->title('Venta a Cuotas Anulada')
                            ->body('El contrato ha sido cancelado y las existencias fueron reintegradas al inventario.')
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public static function actualizarSubtotalGeneral(Get $get, Set $set): void
    {
        $items = $get('detalles') ?? [];
        $sumaBase = 0;

        foreach ($items as $item) {
            $cant = (float) ($item['cantidad'] ?? 1);
            $precio = (float) ($item['precio_unitario'] ?? 0);
            $desc = (float) ($item['descuento'] ?? 0);
            $sumaBase += max(0, ($cant * $precio) - $desc);
        }

        $set('precio_base', round($sumaBase, 2));
        static::recalcularTotales($get, $set);
    }

    public static function recalcalcularTotales(Get $get, Set $set): void
    {
        $precio = (float) ($get('precio_base') ?? 0);
        $descuento = (float) ($get('descuento') ?? 0);
        $envio = (float) ($get('envio_otros') ?? 0);
        $tasaInteres = (float) ($get('porcentaje_interes') ?? 0);
        $enganche = (float) ($get('enganche') ?? 0);

        $base = max(0, $precio - $descuento + $envio);
        $montoInteres = round($base * ($tasaInteres / 100), 2);
        $granTotal = round($base + $montoInteres, 2);
        $saldoFinanciar = max(0, round($granTotal - $enganche, 2));

        $set('total', $granTotal);
        $set('saldo_financiar', $saldoFinanciar);
        $set('saldo_pendiente', $saldoFinanciar);

        static::generarTablaCuotas($get, $set);
    }

    public static function generarTablaCuotas(Get $get, Set $set): void
    {
        $saldoRestante = (float) ($get('saldo_financiar') ?? 0);
        $numCuotas = max(1, (int) ($get('numero_cuotas') ?? 1));
        $diasIntervalo = max(1, (int) ($get('dias_intervalo') ?? 30));
        $fechaInicioStr = $get('fecha_inicio') ?? now()->toDateString();
        $clienteId = $get('cliente_id');

        if ($saldoRestante <= 0) {
            $set('cuotas', []);
            return;
        }

        $cuotaBase = round($saldoRestante / $numCuotas, 2);
        $cuotas = [];
        $acumulado = 0;
        $fecha = Carbon::parse($fechaInicioStr);

        for ($i = 1; $i <= $numCuotas; $i++) {
            $fecha = $fecha->copy()->addDays($diasIntervalo);

            if ($i === $numCuotas) {
                $monto = round($saldoRestante - $acumulado, 2);
            } else {
                $monto = $cuotaBase;
                $acumulado += $monto;
            }

            $cuotas[] = [
                'numero_cuota' => $i,
                'monto_cuota' => $monto,
                'fecha_vencimiento' => $fecha->toDateString(),
                'monto_pagado' => 0.00,
                'saldo_pendiente' => $monto,
                'estado' => 'PENDIENTE',
                'cliente_id' => $clienteId,
            ];
        }

        $set('cuotas', $cuotas);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentaCuotas::route('/'),
            'create' => Pages\CreateVentaCuota::route('/create'),
            'view' => Pages\ViewVentaCuota::route('/{record}'),
        ];
    }
}