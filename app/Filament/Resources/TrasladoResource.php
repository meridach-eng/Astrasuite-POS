<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrasladoResource\Pages;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Traslado;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class TrasladoResource extends Resource
{
    protected static ?string $model = Traslado::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Catálogo / Inventario';

    protected static ?string $navigationLabel = 'Traslados';

    protected static ?string $modelLabel = 'Traslado';

    protected static ?string $pluralModelLabel = 'Traslados';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Traslado')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('numero_referencia')
                            ->label('N° de referencia')
                            ->default(fn () => 'TRF-' . now()->format('Y') . '-' . strtoupper(substr(uniqid(), -4)))
                            ->readOnly()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\DatePicker::make('fecha_traslado')
                            ->label('Fecha')
                            ->default(now())
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('sucursal_origen_id')
                            ->label('Sucursal de origen')
                            ->options(Sucursal::where('activo', true)->pluck('nombre', 'id'))
                            ->default(session('sucursal_activa_id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('detalles', [])),

                        Forms\Components\Select::make('sucursal_destino_id')
                            ->label('Sucursal de destino')
                            ->options(function (Get $get) {
                                $origenId = $get('sucursal_origen_id');
                                return Sucursal::where('activo', true)
                                    ->when($origenId, fn ($query) => $query->where('id', '!=', $origenId))
                                    ->pluck('nombre', 'id');
                            })
                            ->required()
                            ->live()
                            ->rule(function (Get $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $origenId = $get('sucursal_origen_id');
                                    if ($value && $origenId && $value === $origenId) {
                                        $fail('La sucursal de destino no puede ser la misma que la sucursal de origen.');
                                    }
                                };
                            }),

                        Forms\Components\Hidden::make('estado')
                            ->default('ENVIADO')
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => auth()->id())
                            ->dehydrated(),
                    ]),

                Forms\Components\Section::make('Artículos a Trasladar')
                    ->schema([
                        Forms\Components\Repeater::make('detalles')
                            ->relationship('detalles')
                            ->schema([
                                Forms\Components\Select::make('producto_id')
                                    ->label('Artículo / Producto')
                                    ->options(function (Get $get) {
                                        $origenId = $get('../../sucursal_origen_id');
                                        if (! $origenId) return [];

                                        return Producto::where('activo', true)
                                            ->where('tipo', 'BIEN')
                                            ->get()
                                            ->mapWithKeys(fn ($p) => [$p->id => "{$p->nombre} (SKU: {$p->sku})"]);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('lote_id', null))
                                    ->columnSpan(['md' => 5]),

                                Forms\Components\Select::make('lote_id')
                                    ->label('Lote (Si aplica)')
                                    ->options(function (Get $get) {
                                        $prodId = $get('producto_id');
                                        $origenId = $get('../../sucursal_origen_id');
                                        if (! $prodId || ! $origenId) return [];

                                        return Lote::where('producto_id', $prodId)
                                            ->where('sucursal_id', $origenId)
                                            ->where('cantidad_actual', '>', 0)
                                            ->get()
                                            ->mapWithKeys(fn ($l) => [$l->id => "Lote: {$l->numero_lote} (Disp: {$l->cantidad_actual})"]);
                                    })
                                    ->visible(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes)
                                    ->columnSpan(['md' => 4]),

                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->rule(function (Get $get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $prodId = $get('producto_id');
                                            $origenId = $get('../../sucursal_origen_id');
                                            $loteId = $get('lote_id');

                                            if (!$prodId || !$origenId) return;

                                            $prod = Producto::find($prodId);
                                            if (!$prod) return;

                                            $pivot = $prod->sucursales()->where('sucursal_id', $origenId)->first();
                                            $stockDisponble = $pivot ? (float) $pivot->pivot->stock_actual : 0;

                                            if ((float) $value > $stockDisponble) {
                                                $fail("Stock insuficiente en origen. Disponible: {$stockDisponble}");
                                            }

                                            if ($prod->maneja_lotes && $loteId) {
                                                $lote = Lote::find($loteId);
                                                if ($lote && (float) $value > (float) $lote->cantidad_actual) {
                                                    $fail("Stock insuficiente en el lote seleccionado. Disponible: {$lote->cantidad_actual}");
                                                }
                                            }
                                        };
                                    })
                                    ->columnSpan(['md' => 3]),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Observaciones y Notas')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Textarea::make('nota_remitente')
                            ->label('Nota para el remitente')
                            ->placeholder('Instrucciones o comentarios de salida...'),

                        Forms\Components\Textarea::make('nota_receptor')
                            ->label('Nota para el receptor')
                            ->placeholder('Comentarios para la sucursal de destino...'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha_traslado')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('numero_referencia')
                    ->label('N° Referencia')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('sucursalOrigen.nombre')
                    ->label('Origen')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sucursalDestino.nombre')
                    ->label('Destino')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ENVIADO' => 'warning',
                        'RECIBIDO' => 'success',
                        'ANULADO', 'ANULADA' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Enviado por'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                // Acción Recibir: Acredita en destino (el origen ya se descontó al crear)
                Tables\Actions\Action::make('recibir')
                    ->label('Recibir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('¿Confirmar Recepción de Traslado?')
                    ->modalDescription('Esto acreditará los artículos en la sucursal de destino.')
                    ->visible(fn (Traslado $record) => $record->estado === 'ENVIADO')
                    ->action(function (Traslado $record) {
                        DB::transaction(function () use ($record) {
                            $record->load('detalles');
                            $destinoId = $record->sucursal_destino_id;

                            foreach ($record->detalles as $detalle) {
                                $producto = Producto::lockForUpdate()->find($detalle->producto_id);
                                if (! $producto) continue;

                                $cant = (float) $detalle->cantidad;

                                // 1. Sumar stock a destino
                                $pivotDestino = $producto->sucursales()->where('sucursal_id', $destinoId)->first();
                                if ($pivotDestino) {
                                    $nuevoStockDestino = (float) $pivotDestino->pivot->stock_actual + $cant;
                                    $producto->sucursales()->updateExistingPivot($destinoId, ['stock_actual' => $nuevoStockDestino]);
                                } else {
                                    $producto->sucursales()->attach($destinoId, ['stock_actual' => $cant]);
                                }

                                // 2. Crear lote en destino si aplica
                                if ($detalle->lote_id) {
                                    $loteOrigen = Lote::find($detalle->lote_id);
                                    if ($loteOrigen) {
                                        Lote::create([
                                            'producto_id' => $producto->id,
                                            'sucursal_id' => $destinoId,
                                            'numero_lote' => $loteOrigen->numero_lote . '-TRF',
                                            'cantidad_inicial' => $cant,
                                            'cantidad_actual' => $cant,
                                            'costo_unitario' => $loteOrigen->costo_unitario ?? $producto->precio_compra,
                                            'fecha_vencimiento' => $loteOrigen->fecha_vencimiento,
                                        ]);
                                    }
                                }
                            }

                            $record->update(['estado' => 'RECIBIDO']);
                        });

                        Notification::make()
                            ->title('Traslado Recibido Exitosamente')
                            ->body('El inventario ha sido acreditado en la sucursal de destino.')
                            ->success()
                            ->send();
                    }),

                // Acción Anular: Restituye el inventario al origen (ya que se había descontado al crear)
                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Anular Traslado en Tránsito?')
                    ->modalDescription('Esta acción cancelará el traslado y devolverá el inventario a la sucursal de origen.')
                    ->visible(fn (Traslado $record) => $record->estado === 'ENVIADO')
                    ->action(function (Traslado $record) {
                        DB::transaction(function () use ($record) {
                            $record->load('detalles');
                            $origenId = $record->sucursal_origen_id;

                            foreach ($record->detalles as $detalle) {
                                $producto = Producto::lockForUpdate()->find($detalle->producto_id);
                                if (! $producto) continue;

                                $cant = (float) $detalle->cantidad;

                                // Devolver stock general a origen
                                $pivot = $producto->sucursales()->where('sucursal_id', $origenId)->first();
                                if ($pivot) {
                                    $stockRestaurado = (float) $pivot->pivot->stock_actual + $cant;
                                    $producto->sucursales()->updateExistingPivot($origenId, ['stock_actual' => $stockRestaurado]);
                                }

                                // Devolver stock al lote de origen
                                if ($detalle->lote_id) {
                                    $lote = Lote::lockForUpdate()->find($detalle->lote_id);
                                    if ($lote) {
                                        $lote->increment('cantidad_actual', $cant);
                                    }
                                }
                            }

                            $record->update(['estado' => 'ANULADO']);
                        });

                        Notification::make()
                            ->title('Traslado Anulado')
                            ->body('El inventario fue devuelto a la sucursal de origen correctamente.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTraslados::route('/'),
            'create' => Pages\CreateTraslado::route('/create'),
            'view' => Pages\ViewTraslado::route('/{record}'),
        ];
    }
}