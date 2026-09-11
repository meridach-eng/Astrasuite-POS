<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalidaInventarioResource\Pages;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\SalidaInventario;
use App\Models\Sucursal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class SalidaInventarioResource extends Resource
{
    protected static ?string $model = SalidaInventario::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-down';

    protected static ?string $navigationGroup = 'Catálogo / Inventario';

    protected static ?string $navigationLabel = 'Salidas / Mermas';

    protected static ?string $modelLabel = 'Salida de Inventario';

    protected static ?string $pluralModelLabel = 'Salidas de Inventario';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Información General de la Salida')
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('numero_referencia')
                                    ->label('No. Referencia')
                                    ->default(fn () => 'SAL-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\Select::make('sucursal_id')
                                    ->label('Sucursal de Origen')
                                    ->options(Sucursal::where('activo', true)->pluck('nombre', 'id'))
                                    ->default(session('sucursal_activa_id'))
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('tipo_salida')
                                    ->label('Motivo de Salida')
                                    ->options([
                                        'USO_INTERNO' => 'Uso Interno / Tratamiento',
                                        'DAÑO' => 'Producto Dañado / Roto',
                                        'VENCIMIENTO' => 'Vencimiento / Caducado',
                                        'EXTRAVIO' => 'Extravío / Faltante / Robado',
                                    ])
                                    ->default('USO_INTERNO')
                                    ->required(),

                                Forms\Components\DatePicker::make('fecha_salida')
                                    ->label('Fecha del Movimiento')
                                    ->default(now())
                                    ->required()
                                    ->native(false),

                                Forms\Components\Hidden::make('user_id')
                                    ->default(fn () => auth()->id())
                                    ->dehydrated(),

                                Forms\Components\Textarea::make('observaciones')
                                    ->label('Notas / Justificación')
                                    ->placeholder('Detalles de la salida o destino del insumo...')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Artículos a Retirar del Inventario')
                            ->schema([
                                Forms\Components\Repeater::make('detalles')
                                    ->relationship('detalles')
                                    ->schema([
                                        // FILA 1: SELECTOR DE PRODUCTO EN ANCHO COMPLETO
                                        Forms\Components\Select::make('producto_id')
                                            ->label('Producto')
                                            ->options(function (Get $get) {
                                                $sucursalId = $get('../../sucursal_id');
                                                if (! $sucursalId) return [];

                                                return Producto::where('activo', true)
                                                    ->where('tipo', 'BIEN')
                                                    ->get()
                                                    ->mapWithKeys(fn ($p) => [$p->id => "{$p->nombre} (Costo: Q{$p->precio_compra})"]);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                if (! $state) return;
                                                $prod = Producto::find($state);
                                                if ($prod) {
                                                    $set('costo_unitario', (float) $prod->precio_compra);
                                                    $set('cantidad', 1);
                                                    $set('subtotal', round((float) $prod->precio_compra * 1, 2));
                                                }
                                            })
                                            ->columnSpanFull(),

                                        // FILA 2: DISTRIBUCIÓN ESPACIOSA DE LOTE, CANTIDAD, COSTO Y TOTAL
                                        Forms\Components\Grid::make(12)
                                            ->schema([
                                                Forms\Components\Select::make('lote_id')
                                                    ->label('Lote (PEPS)')
                                                    ->options(function (Get $get) {
                                                        $prodId = $get('producto_id');
                                                        $sucursalId = $get('../../sucursal_id');
                                                        if (! $prodId || ! $sucursalId) return [];

                                                        return Lote::where('producto_id', $prodId)
                                                            ->where('sucursal_id', $sucursalId)
                                                            ->where('cantidad_actual', '>', 0)
                                                            ->get()
                                                            ->mapWithKeys(fn ($l) => [$l->id => "Lote: {$l->numero_lote} (Disp: {$l->cantidad_actual})"]);
                                                    })
                                                    ->visible(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes)
                                                    ->required(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes)
                                                    ->columnSpan(4),

                                                Forms\Components\TextInput::make('cantidad')
                                                    ->label('Cantidad')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(0.01)
                                                    ->required()
                                                    ->live(debounce: 250)
                                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                        $cant = (float) $state;
                                                        $costo = (float) ($get('costo_unitario') ?? 0);
                                                        $set('subtotal', round($cant * $costo, 2));
                                                    })
                                                    ->columnSpan(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes ? 2 : 4),

                                                Forms\Components\TextInput::make('costo_unitario')
                                                    ->label('Costo Unit.')
                                                    ->numeric()
                                                    ->prefix('Q')
                                                    ->required()
                                                    ->live(debounce: 250)
                                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                        $costo = (float) $state;
                                                        $cant = (float) ($get('cantidad') ?? 0);
                                                        $set('subtotal', round($cant * $costo, 2));
                                                    })
                                                    ->columnSpan(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes ? 3 : 4),

                                                Forms\Components\TextInput::make('subtotal')
                                                    ->label('Total Pérdida')
                                                    ->numeric()
                                                    ->prefix('Q')
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->extraInputAttributes(['style' => 'font-weight: 700; color: #dc2626;'])
                                                    ->columnSpan(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes ? 3 : 4),
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::calcularCostoTotal($get, $set))
                                    ->defaultItems(1)
                                    ->addActionLabel('Agregar otro producto'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Resumen Financiero')
                            ->schema([
                                Forms\Components\TextInput::make('costo_total')
                                    ->label('Valor Total de Salida')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->extraInputAttributes(['class' => 'text-xl font-bold text-red-600']),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function calcularCostoTotal(Get $get, Set $set): void
    {
        $detalles = $get('detalles') ?? [];
        $total = 0;

        foreach ($detalles as $det) {
            $cant = (float) ($det['cantidad'] ?? 0);
            $costo = (float) ($det['costo_unitario'] ?? 0);
            $total += ($cant * $costo);
        }

        $set('costo_total', round($total, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha_salida')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('numero_referencia')
                    ->label('Referencia')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_salida')
                    ->label('Motivo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'USO_INTERNO' => 'info',
                        'DAÑO' => 'danger',
                        'VENCIMIENTO' => 'warning',
                        'EXTRAVIO' => 'gray',
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Responsable'),

                Tables\Columns\TextColumn::make('costo_total')
                    ->label('Costo Total')
                    ->money('GTQ')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'COMPLETADA' => 'success',
                        'ANULADA' => 'danger',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                // ACCIÓN DE ANULAR Y REVERTIR INVENTARIO
                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Anular Salida de Inventario?')
                    ->modalDescription('Esta acción devolverá los artículos al inventario y lotes originales de la sucursal. Esta operación no se puede deshacer.')
                    ->visible(fn (SalidaInventario $record) => $record->estado === 'COMPLETADA')
                    ->action(function (SalidaInventario $record) {
                        DB::transaction(function () use ($record) {
                            $record->load('detalles');

                            foreach ($record->detalles as $detalle) {
                                $producto = Producto::lockForUpdate()->find($detalle->producto_id);
                                if (! $producto) continue;

                                $sucursalId = $record->sucursal_id;
                                $cantidadDevolver = (float) $detalle->cantidad;

                                // 1. Revertir stock general en la sucursal
                                $pivot = $producto->sucursales()->where('sucursal_id', $sucursalId)->first();
                                if ($pivot) {
                                    $nuevoStock = (float) $pivot->pivot->stock_actual + $cantidadDevolver;
                                    $producto->sucursales()->updateExistingPivot($sucursalId, [
                                        'stock_actual' => $nuevoStock,
                                    ]);
                                }

                                // 2. Revertir lote específico si aplica
                                if ($detalle->lote_id) {
                                    $lote = Lote::lockForUpdate()->find($detalle->lote_id);
                                    if ($lote) {
                                        $lote->increment('cantidad_actual', $cantidadDevolver);
                                    }
                                }
                            }

                            // Marcar como anulada
                            $record->update(['estado' => 'ANULADA']);
                        });

                        Notification::make()
                            ->title('Salida Anulada Exitosamente')
                            ->body('El inventario ha sido devuelto a la sucursal.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalidaInventarios::route('/'),
            'create' => Pages\CreateSalidaInventario::route('/create'),
            'view' => Pages\ViewSalidaInventario::route('/{record}'),
        ];
    }
}