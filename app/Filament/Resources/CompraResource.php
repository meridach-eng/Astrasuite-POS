<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompraResource\Pages;
use App\Models\Compra;
use App\Models\Lote;
use App\Models\PagoCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sucursal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class CompraResource extends Resource
{
    protected static ?string $model = Compra::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Compras y Proveedores';

    protected static ?string $navigationLabel = 'Compras / Entradas';

    protected static ?string $modelLabel = 'Compra';

    protected static ?string $pluralModelLabel = 'Compras';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Datos del Documento y Proveedor')
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('numero_referencia')
                                    ->label('No. Referencia')
                                    ->default(fn () => 'PUR-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\Select::make('sucursal_id')
                                    ->label('Sucursal de Destino')
                                    ->options(Sucursal::where('activo', true)->pluck('nombre', 'id'))
                                    ->default(session('sucursal_activa_id'))
                                    ->required(),

                                Forms\Components\Select::make('proveedor_id')
                                    ->label('Proveedor')
                                    ->relationship('proveedor', 'nombre')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nombre')
                                            ->label('Nombre o Razón Social')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('nit')
                                            ->label('NIT o Documento')
                                            ->placeholder('Ej. 7483920-1 o CF')
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('telefono')
                                            ->label('Teléfono / WhatsApp')
                                            ->tel()
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('contacto')
                                            ->label('Nombre de Contacto')
                                            ->placeholder('Ej. Carlos Mendoza')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('email')
                                            ->label('Correo Electrónico')
                                            ->email()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('direccion')
                                            ->label('Dirección')
                                            ->default('Ciudad')
                                            ->maxLength(255),

                                        Forms\Components\Hidden::make('activo')
                                            ->default(true),
                                    ])
                                    ->createOptionModalHeading('Registrar Nuevo Proveedor'),

                                Forms\Components\DatePicker::make('fecha_compra')
                                    ->label('Fecha del Documento')
                                    ->default(now())
                                    ->required()
                                    ->native(false),

                                Forms\Components\TextInput::make('serie_comprobante')
                                    ->label('Serie Documento')
                                    ->placeholder('Ej. F84A'),

                                Forms\Components\TextInput::make('numero_comprobante')
                                    ->label('No. Factura Proveedor')
                                    ->placeholder('Ej. 4534')
                                    ->required(),

                                Forms\Components\Select::make('estado_recepcion')
                                    ->label('Estado de Recepción Física')
                                    ->options([
                                        'RECIBIDO' => 'Recibido (Afecta Inventario Inmediato)',
                                        'PENDIENTE' => 'Pendiente de Ingreso a Bodega',
                                    ])
                                    ->default('RECIBIDO')
                                    ->required()
                                    ->helperText('Solo si está RECIBIDO se sumará el stock y se generarán los lotes.'),
                            ]),

                        Forms\Components\Section::make('Artículos de la Compra')
                            ->schema([
                                Forms\Components\Repeater::make('detalles')
                                    ->relationship('detalles')
                                    ->schema([
                                        // RENGLÓN 1: PRODUCTO (ANCHO COMPLETO)
                                        Forms\Components\Select::make('producto_id')
                                            ->label('Producto')
                                            ->options(function () {
                                                return Producto::where('activo', true)
                                                    ->where('tipo', 'BIEN')
                                                    ->get()
                                                    ->mapWithKeys(fn ($p) => [$p->id => "{$p->nombre} (SKU: {$p->codigo_interno})"]);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                if (! $state) return;
                                                $prod = Producto::find($state);
                                                if ($prod) {
                                                    $costo = (float) $prod->precio_compra;
                                                    $cant = (float) ($get('cantidad') ?? 1);
                                                    $set('precio_unitario', $costo);
                                                    $set('subtotal', round($cant * $costo, 2));
                                                }
                                            })
                                            ->columnSpanFull(),

                                        // RENGLÓN 2: CANTIDAD, PRECIO COSTO Y TOTAL (3 COLUMNAS LIMPIAS)
                                        Forms\Components\Grid::make(12)
                                            ->schema([
                                                Forms\Components\TextInput::make('cantidad')
                                                    ->label('Cantidad')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(0.01)
                                                    ->required()
                                                    ->live(debounce: 250)
                                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                        $cant = (float) $state;
                                                        $precio = (float) ($get('precio_unitario') ?? 0);
                                                        $set('subtotal', round($cant * $precio, 2));
                                                    })
                                                    ->columnSpan(4),

                                                Forms\Components\TextInput::make('precio_unitario')
                                                    ->label('Precio Costo')
                                                    ->numeric()
                                                    ->prefix('Q')
                                                    ->required()
                                                    ->live(debounce: 250)
                                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                        $precio = (float) $state;
                                                        $cant = (float) ($get('cantidad') ?? 0);
                                                        $set('subtotal', round($cant * $precio, 2));
                                                    })
                                                    ->columnSpan(4),

                                                Forms\Components\TextInput::make('subtotal')
                                                    ->label('Total')
                                                    ->numeric()
                                                    ->prefix('Q')
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->extraInputAttributes(['style' => 'font-weight: 700; color: #0f172a;'])
                                                    ->columnSpan(4),
                                            ])
                                            ->columnSpanFull(),

                                        // RENGLÓN 3: No. LOTE Y FECHA DE VENCIMIENTO (SOLO SI MANEJA LOTES)
                                        Forms\Components\Grid::make(12)
                                            ->visible(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes)
                                            ->schema([
                                                Forms\Components\TextInput::make('numero_lote')
                                                    ->label('No. Lote')
                                                    ->placeholder('Ej. L-202609')
                                                    ->required(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes)
                                                    ->columnSpan(6),

                                                Forms\Components\DatePicker::make('fecha_vencimiento')
                                                    ->label('Fecha Vencimiento')
                                                    ->native(false)
                                                    ->required(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes)
                                                    ->columnSpan(6),
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::calcularTotalesYBalance($get, $set))
                                    ->defaultItems(1)
                                    ->addActionLabel('Agregar otro producto'),
                            ]),

                        Forms\Components\Section::make('Auditoría de Anulación')
                            ->visible(fn (?Compra $record) => $record && $record->estado_recepcion === 'ANULADO')
                            ->schema([
                                Forms\Components\Textarea::make('motivo_anulacion')
                                    ->label('Motivo de Anulación')
                                    ->readOnly(),
                                Forms\Components\DateTimePicker::make('anulado_at')
                                    ->label('Fecha y Hora de Anulación')
                                    ->readOnly(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Resumen Financiero')
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->readOnly()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('descuento')
                                    ->label('Descuento General')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->default(0)
                                    ->live(debounce: 250)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::calcularTotalesYBalance($get, $set)),

                                Forms\Components\TextInput::make('total')
                                    ->label('Gran Total')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->extraInputAttributes(['class' => 'text-xl font-bold']),
                            ]),

                        Forms\Components\Section::make('Pagos Realizados')
                            ->description('Registra una o más formas de pago para saldar la factura.')
                            ->schema([
                                Forms\Components\Repeater::make('pagos')
                                    ->relationship('pagos')
                                    ->schema([
                                        Forms\Components\Select::make('metodo_pago')
                                            ->label('Método')
                                            ->options([
                                                'EFECTIVO' => 'Efectivo',
                                                'TRANSFERENCIA' => 'Transferencia / Banco',
                                                'TARJETA' => 'Tarjeta',
                                                'CHEQUE' => 'Cheque',
                                                'OTRO' => 'Otro',
                                            ])
                                            ->default('EFECTIVO')
                                            ->required()
                                            ->columnSpan(5),

                                        Forms\Components\TextInput::make('monto')
                                            ->label('Monto')
                                            ->numeric()
                                            ->prefix('Q')
                                            ->required()
                                            ->live(debounce: 250)
                                            ->afterStateUpdated(fn (Get $get, Set $set) => self::calcularTotalesYBalance($get, $set))
                                            ->columnSpan(5),

                                        Forms\Components\TextInput::make('referencia_pago')
                                            ->label('Referencia / Boleta')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(10)
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::calcularTotalesYBalance($get, $set))
                                    ->addActionLabel('Agregar método de pago'),

                                Forms\Components\TextInput::make('monto_pagado')
                                    ->label('Total Abonado')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->readOnly()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('saldo_pendiente')
                                    ->label('Saldo Pendiente')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->extraInputAttributes(['class' => 'text-red-600 font-bold']),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function calcularTotalesYBalance(Get $get, Set $set): void
    {
        $detalles = $get('detalles') ?? [];
        $subtotal = 0;

        foreach ($detalles as $det) {
            $cant = (float) ($det['cantidad'] ?? 0);
            $precio = (float) ($det['precio_unitario'] ?? 0);
            $subtotal += ($cant * $precio);
        }

        $descuento = (float) ($get('descuento') ?? 0);
        $total = max(0, round($subtotal - $descuento, 2));

        $set('subtotal', round($subtotal, 2));
        $set('total', $total);

        $pagos = $get('pagos') ?? [];
        $pagado = 0;
        foreach ($pagos as $pago) {
            $pagado += (float) ($pago['monto'] ?? 0);
        }

        $saldoPendiente = max(0, round($total - $pagado, 2));

        $set('monto_pagado', round($pagado, 2));
        $set('saldo_pendiente', $saldoPendiente);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha_compra')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('numero_referencia')
                    ->label('Referencia')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Gran Total')
                    ->money('GTQ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo_pendiente')
                    ->label('Saldo Deudor')
                    ->money('GTQ')
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'gray')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('estado_recepcion')
                    ->label('Recepción')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'RECIBIDO' => 'success',
                        'PENDIENTE' => 'warning',
                        'ANULADO' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('estado_pago')
                    ->label('Pago')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PAGADO' => 'success',
                        'PARCIAL' => 'warning',
                        'PENDIENTE' => 'danger',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estado_recepcion')
                    ->label('Estado Recepción')
                    ->options([
                        'RECIBIDO' => 'Recibido',
                        'PENDIENTE' => 'Pendiente',
                        'ANULADO' => 'Anulado',
                    ]),
                Tables\Filters\SelectFilter::make('estado_pago')
                    ->label('Estado Pago')
                    ->options([
                        'PAGADO' => 'Pagado',
                        'PARCIAL' => 'Pago Parcial',
                        'PENDIENTE' => 'Pendiente / Crédito',
                    ]),
                Tables\Filters\SelectFilter::make('proveedor_id')
                    ->relationship('proveedor', 'nombre'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('registrar_abono')
                    ->label('Abonar')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn (Compra $record) => $record->estado_recepcion !== 'ANULADO' && ((float) $record->saldo_pendiente > 0 || $record->estado_pago !== 'PAGADO'))
                    ->modalHeading(fn (Compra $record) => "Registrar Abono - {$record->numero_referencia}")
                    ->modalDescription(fn (Compra $record) => "Proveedor: {$record->proveedor->nombre} | Saldo Pendiente: Q" . number_format($record->saldo_pendiente, 2))
                    ->form([
                        Forms\Components\TextInput::make('monto')
                            ->label('Monto a Abonar (Q)')
                            ->numeric()
                            ->prefix('Q')
                            ->default(fn (Compra $record) => (float) $record->saldo_pendiente > 0 ? (float) $record->saldo_pendiente : 0.01)
                            ->minValue(0.01)
                            ->required(),

                        Forms\Components\Select::make('metodo_pago')
                            ->label('Método de Pago')
                            ->options([
                                'EFECTIVO' => 'Efectivo',
                                'TRANSFERENCIA' => 'Transferencia / Banco',
                                'TARJETA' => 'Tarjeta',
                                'CHEQUE' => 'Cheque',
                                'OTRO' => 'Otro',
                            ])
                            ->default('EFECTIVO')
                            ->required(),

                        Forms\Components\TextInput::make('referencia_pago')
                            ->label('No. Boleta / Referencia / Cheque')
                            ->placeholder('Ej. Depósito #1049281'),
                    ])
                    ->action(function (Compra $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $montoAbono = (float) $data['monto'];

                            PagoCompra::create([
                                'compra_id' => $record->id,
                                'metodo_pago' => $data['metodo_pago'],
                                'monto' => $montoAbono,
                                'referencia_pago' => $data['referencia_pago'] ?? null,
                            ]);

                            $nuevoMontoPagado = round((float) $record->monto_pagado + $montoAbono, 2);
                            $nuevoSaldo = max(0, round((float) $record->total - $nuevoMontoPagado, 2));
                            $nuevoEstadoPago = $nuevoSaldo <= 0 ? 'PAGADO' : 'PARCIAL';

                            $record->update([
                                'monto_pagado' => $nuevoMontoPagado,
                                'saldo_pendiente' => $nuevoSaldo,
                                'estado_pago' => $nuevoEstadoPago,
                            ]);
                        });

                        Notification::make()
                            ->title('Abono Registrado Exitosamente')
                            ->body('El saldo deudor de la compra ha sido actualizado.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('recibir_mercaderia')
                    ->label('Recibir')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Compra $record) => "Confirmar Recepción: {$record->numero_referencia}")
                    ->modalDescription(fn (Compra $record) => "Esta acción sumará físicamente los artículos al inventario de {$record->sucursal->nombre}, creará los lotes PEPS y actualizará los costos promedio (AVCO).")
                    ->visible(fn (Compra $record) => $record->estado_recepcion === 'PENDIENTE')
                    ->action(function (Compra $record) {
                        DB::transaction(function () use ($record) {
                            foreach ($record->detalles as $detalle) {
                                $producto = Producto::lockForUpdate()->find($detalle->producto_id);

                                if (! $producto || $producto->tipo !== 'BIEN') {
                                    continue;
                                }

                                $sucursalId = $record->sucursal_id;
                                $cantidadComprada = (float) $detalle->cantidad;
                                $costoUnitarioCompra = (float) $detalle->precio_unitario;

                                $stockGlobalPrevio = (float) $producto->sucursales()->sum('stock_actual');
                                $costoPrevio = (float) $producto->precio_compra;

                                $pivot = $producto->sucursales()->where('sucursal_id', $sucursalId)->first();
                                if ($pivot) {
                                    $producto->sucursales()->updateExistingPivot($sucursalId, [
                                        'stock_actual' => (float) $pivot->pivot->stock_actual + $cantidadComprada,
                                    ]);
                                } else {
                                    $producto->sucursales()->attach($sucursalId, [
                                        'stock_actual' => $cantidadComprada,
                                        'stock_minimo_sucursal' => $producto->stock_minimo,
                                    ]);
                                }

                                $nuevoStockGlobal = $stockGlobalPrevio + $cantidadComprada;
                                if ($nuevoStockGlobal > 0) {
                                    $nuevoCostoAVCO = (($stockGlobalPrevio * $costoPrevio) + ($cantidadComprada * $costoUnitarioCompra)) / $nuevoStockGlobal;
                                    $margen = (float) $producto->margen_utilidad;
                                    $nuevoPrecioVenta = round($nuevoCostoAVCO + ($nuevoCostoAVCO * ($margen / 100)), 2);

                                    $producto->update([
                                        'precio_compra' => round($nuevoCostoAVCO, 4),
                                        'precio_venta' => $nuevoPrecioVenta,
                                    ]);
                                }

                                if ($producto->maneja_lotes && $detalle->numero_lote) {
                                    Lote::create([
                                        'producto_id' => $producto->id,
                                        'sucursal_id' => $sucursalId,
                                        'compra_id' => $record->id,
                                        'numero_lote' => $detalle->numero_lote,
                                        'fecha_vencimiento' => $detalle->fecha_vencimiento,
                                        'cantidad_inicial' => $cantidadComprada,
                                        'cantidad_actual' => $cantidadComprada,
                                        'costo_unitario' => $costoUnitarioCompra,
                                        'activo' => true,
                                    ]);
                                }
                            }

                            $record->update([
                                'estado_recepcion' => 'RECIBIDO',
                            ]);
                        });

                        Notification::make()
                            ->title('Mercadería Recibida en Bodega')
                            ->body('El inventario y los lotes han sido actualizados con éxito.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Compra $record) => "Anular Compra {$record->numero_referencia}")
                    ->modalDescription('Esta acción revertirá las existencias físicas de la sucursal, eliminará los lotes creados y cancelará el saldo pendiente.')
                    ->visible(fn (Compra $record) => $record->estado_recepcion !== 'ANULADO')
                    ->form([
                        Forms\Components\Textarea::make('motivo_anulacion')
                            ->label('Motivo de Anulación')
                            ->required()
                            ->placeholder('Ej. Factura duplicada / Error de digitación'),
                    ])
                    ->action(function (Compra $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            if ($record->estado_recepcion === 'RECIBIDO') {
                                foreach ($record->detalles as $detalle) {
                                    $producto = Producto::lockForUpdate()->find($detalle->producto_id);
                                    if ($producto && $producto->tipo === 'BIEN') {
                                        $pivot = $producto->sucursales()->where('sucursal_id', $record->sucursal_id)->first();
                                        if ($pivot) {
                                            $nuevoStock = max(0, (float) $pivot->pivot->stock_actual - (float) $detalle->cantidad);
                                            $producto->sucursales()->updateExistingPivot($record->sucursal_id, [
                                                'stock_actual' => $nuevoStock,
                                            ]);
                                        }
                                    }
                                }

                                Lote::where('compra_id', $record->id)->delete();
                            }

                            $record->update([
                                'estado_recepcion' => 'ANULADO',
                                'saldo_pendiente' => 0,
                                'motivo_anulacion' => $data['motivo_anulacion'],
                                'anulado_at' => now(),
                            ]);
                        });

                        Notification::make()
                            ->title('Compra Anulada')
                            ->body('El inventario fue revertido exitosamente.')
                            ->danger()
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
                        Infolists\Components\Section::make('Información del Documento')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('numero_referencia')
                                    ->label('Referencia')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('numero_comprobante')
                                    ->label('No. Factura Proveedor'),
                                Infolists\Components\TextEntry::make('fecha_compra')
                                    ->label('Fecha')
                                    ->date('d/m/Y'),
                                Infolists\Components\TextEntry::make('proveedor.nombre')
                                    ->label('Proveedor'),
                                Infolists\Components\TextEntry::make('sucursal.nombre')
                                    ->label('Sucursal'),
                                Infolists\Components\TextEntry::make('estado_recepcion')
                                    ->label('Recepción')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'RECIBIDO' => 'success',
                                        'PENDIENTE' => 'warning',
                                        'ANULADO' => 'danger',
                                    }),
                            ]),

                        Infolists\Components\Section::make('Artículos Ingresados')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('detalles')
                                    ->label('')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('producto.nombre')
                                            ->label('Producto')
                                            ->columnSpan(4),
                                        Infolists\Components\TextEntry::make('numero_lote')
                                            ->label('Lote Creado')
                                            ->placeholder('-')
                                            ->columnSpan(2),
                                        Infolists\Components\TextEntry::make('cantidad')
                                            ->label('Cant.')
                                            ->columnSpan(1),
                                        Infolists\Components\TextEntry::make('precio_unitario')
                                            ->label('Costo Unit.')
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
                    ])
                    ->columnSpan(['lg' => 2]),

                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make('Estado Financiero de la Compra')
                            ->schema([
                                Infolists\Components\TextEntry::make('total')
                                    ->label('Gran Total')
                                    ->money('GTQ')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('black'),
                                Infolists\Components\TextEntry::make('monto_pagado')
                                    ->label('Total Pagado')
                                    ->money('GTQ')
                                    ->color('success')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('saldo_pendiente')
                                    ->label('Saldo Pendiente')
                                    ->money('GTQ')
                                    ->color('danger')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('estado_pago')
                                    ->label('Estado de Pago')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'PAGADO' => 'success',
                                        'PARCIAL' => 'warning',
                                        'PENDIENTE' => 'danger',
                                    }),
                            ]),

                        Infolists\Components\Section::make('Historial de Abonos / Pagos')
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
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Fecha')
                                            ->dateTime('d/m/Y H:i'),
                                        Infolists\Components\TextEntry::make('referencia_pago')
                                            ->label('Ref.')
                                            ->placeholder('-'),
                                    ])
                                    ->columns(4),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompras::route('/'),
            'create' => Pages\CreateCompra::route('/create'),
            'view' => Pages\ViewCompra::route('/{record}'),
        ];
    }
}