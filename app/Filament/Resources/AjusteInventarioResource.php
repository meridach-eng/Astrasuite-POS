<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AjusteInventarioResource\Pages;
use App\Models\AjusteInventario;
use App\Models\Lote;
use App\Models\Producto;
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

class AjusteInventarioResource extends Resource
{
    protected static ?string $model = AjusteInventario::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Catálogo / Inventario';

    protected static ?string $navigationLabel = 'Ajustes de Inventario';

    protected static ?string $modelLabel = 'Ajuste de Inventario';

    protected static ?string $pluralModelLabel = 'Ajustes de Inventario';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Información del Conteo Físico')
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('numero_referencia')
                                    ->label('No. Referencia')
                                    ->default(fn () => 'AJU-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\Select::make('sucursal_id')
                                    ->label('Sucursal')
                                    ->options(Sucursal::where('activo', true)->pluck('nombre', 'id'))
                                    ->default(session('sucursal_activa_id'))
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('tipo_ajuste')
                                    ->label('Tipo de Ajuste Global')
                                    ->options([
                                        'FALTANTE' => 'Faltante (Merma / Menos stock)',
                                        'SOBRANTE' => 'Sobrante (Más stock)',
                                    ])
                                    ->default('FALTANTE')
                                    ->required(),

                                Forms\Components\DatePicker::make('fecha_ajuste')
                                    ->label('Fecha del Conteo')
                                    ->default(now())
                                    ->required()
                                    ->native(false),

                                Forms\Components\Hidden::make('user_id')
                                    ->default(fn () => auth()->id())
                                    ->dehydrated(),

                                Forms\Components\Textarea::make('observaciones')
                                    ->label('Motivo / Justificación del Arqueo')
                                    ->placeholder('Ej. Conteo físico cíclico mensual, revisión de gavetas...')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Detalle de Artículos Contados')
                            ->schema([
                                Forms\Components\Repeater::make('detalles')
                                    ->relationship('detalles')
                                    ->schema([
                                        // FILA 1: PRODUCTO (ANCHO COMPLETO)
                                        Forms\Components\Select::make('producto_id')
                                            ->label('Producto')
                                            ->options(function (Get $get) {
                                                $sucursalId = $get('../../sucursal_id');
                                                if (! $sucursalId) return [];

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
                                                $sucursalId = $get('../../sucursal_id');
                                                
                                                if ($prod && $sucursalId) {
                                                    $pivot = $prod->sucursales()->where('sucursal_id', $sucursalId)->first();
                                                    $stockSys = $pivot ? (float) $pivot->pivot->stock_actual : 0;
                                                    
                                                    $set('stock_sistema', $stockSys);
                                                    $set('stock_fisico', $stockSys);
                                                    $set('costo_unitario', (float) $prod->precio_compra);
                                                    $set('diferencia', 0);
                                                    $set('subtotal', 0);
                                                }
                                                self::actualizarTotalesGlobales($get, $set);
                                            })
                                            ->columnSpanFull(),

                                        // FILA 2: LOTE (SI APLICA) Y CONTROL DE EXISTENCIAS
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
                                                            ->get()
                                                            ->mapWithKeys(fn ($l) => [$l->id => "Lote: {$l->numero_lote} (Stock: {$l->cantidad_actual})"]);
                                                    })
                                                    ->visible(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes)
                                                    ->columnSpan(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes ? 4 : 0),

                                                Forms\Components\TextInput::make('stock_sistema')
                                                    ->label('En Sistema')
                                                    ->numeric()
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->columnSpan(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes ? 2 : 4),

                                                Forms\Components\TextInput::make('stock_fisico')
                                                    ->label('Conteo Físico')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->live(debounce: 250)
                                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                        $fisico = (float) $state;
                                                        $sistema = (float) ($get('stock_sistema') ?? 0);
                                                        $costo = (float) ($get('costo_unitario') ?? 0);
                                                        
                                                        $dif = $fisico - $sistema;
                                                        $set('diferencia', $dif);
                                                        $set('subtotal', round(abs($dif) * $costo, 2));

                                                        self::actualizarTotalesGlobales($get, $set);
                                                    })
                                                    ->columnSpan(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes ? 3 : 4),

                                                Forms\Components\Hidden::make('diferencia')->default(0),

                                                Forms\Components\Placeholder::make('diferencia_view')
                                                    ->label('Diferencia (+/-)')
                                                    ->content(function (Get $get) {
                                                        $dif = (float) ($get('diferencia') ?? 0);
                                                        $prefijo = $dif > 0 ? '+' : '';
                                                        return $prefijo . number_format($dif, 2);
                                                    })
                                                    ->extraAttributes(function (Get $get) {
                                                        $dif = (float) ($get('diferencia') ?? 0);
                                                        $color = $dif < 0 ? 'color: #dc2626;' : ($dif > 0 ? 'color: #16a34a;' : 'color: #64748b;');
                                                        return [
                                                            'style' => 'font-weight: 800; font-size: 15px; margin-top: 4px; ' . $color,
                                                        ];
                                                    })
                                                    ->columnSpan(fn (Get $get) => (bool) Producto::find($get('producto_id'))?->maneja_lotes ? 3 : 4),
                                            ])
                                            ->columnSpanFull(),

                                        // FILA 3: COSTO E IMPACTO CON ESPACIO AMPLIO (6 Y 6 COLS)
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('costo_unitario')
                                                    ->label('Costo Unitario')
                                                    ->numeric()
                                                    ->prefix('Q')
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->extraInputAttributes(['style' => 'font-weight: 600;']),

                                                Forms\Components\TextInput::make('subtotal')
                                                    ->label('Impacto Financiero del Artículo')
                                                    ->numeric()
                                                    ->prefix('Q')
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->extraInputAttributes(['style' => 'font-weight: 800; color: #0f172a; font-size: 14px;']),
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::actualizarTotalesGlobales($get, $set))
                                    ->defaultItems(1)
                                    ->addActionLabel('Agregar otro producto'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Resumen del Ajuste')
                            ->schema([
                                Forms\Components\TextInput::make('costo_total')
                                    ->label('Impacto Financiero Total')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->extraInputAttributes(['class' => 'text-xl font-bold text-primary-600']),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function actualizarTotalesGlobales(Get $get, Set $set): void
    {
        $detalles = $get('../../detalles') ?? $get('detalles') ?? [];
        $total = 0;

        foreach ($detalles as $det) {
            $dif = abs((float) ($det['diferencia'] ?? 0));
            $costo = (float) ($det['costo_unitario'] ?? 0);
            $total += ($dif * $costo);
        }

        $set('../../costo_total', round($total, 2));
        $set('costo_total', round($total, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha_ajuste')
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

                Tables\Columns\TextColumn::make('tipo_ajuste')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'SOBRANTE' => 'success',
                        'FALTANTE' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Responsable'),

                Tables\Columns\TextColumn::make('costo_total')
                    ->label('Impacto Total')
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

                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Anular Ajuste de Inventario?')
                    ->modalDescription('Esta acción revertirá los cambios aplicados al stock y lotes durante este conteo.')
                    ->visible(fn (AjusteInventario $record) => $record->estado === 'COMPLETADA')
                    ->action(function (AjusteInventario $record) {
                        DB::transaction(function () use ($record) {
                            $record->load('detalles');

                            foreach ($record->detalles as $detalle) {
                                $producto = Producto::lockForUpdate()->find($detalle->producto_id);
                                if (! $producto) continue;

                                $sucursalId = $record->sucursal_id;
                                $diferencia = (float) $detalle->diferencia;
                                $impactoInverso = -1 * $diferencia;

                                $pivot = $producto->sucursales()->where('sucursal_id', $sucursalId)->first();
                                if ($pivot) {
                                    $nuevoStock = max(0, (float) $pivot->pivot->stock_actual + $impactoInverso);
                                    $producto->sucursales()->updateExistingPivot($sucursalId, [
                                        'stock_actual' => $nuevoStock,
                                    ]);
                                }

                                if ($detalle->lote_id) {
                                    $lote = Lote::lockForUpdate()->find($detalle->lote_id);
                                    if ($lote) {
                                        $nuevoLoteStock = max(0, (float) $lote->cantidad_actual + $impactoInverso);
                                        $lote->update(['cantidad_actual' => $nuevoLoteStock]);
                                    }
                                }
                            }

                            $record->update(['estado' => 'ANULADA']);
                        });

                        Notification::make()
                            ->title('Ajuste Anulado Exitosamente')
                            ->body('El inventario ha sido devuelto a su estado previo al conteo.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAjusteInventarios::route('/'),
            'create' => Pages\CreateAjusteInventario::route('/create'),
            'view' => Pages\ViewAjusteInventario::route('/{record}'),
        ];
    }
}