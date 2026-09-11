<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CotizacionResource\Pages;
use App\Models\Cliente;
use App\Models\Cotizacion;
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
use Illuminate\Database\Eloquent\Builder;

class CotizacionResource extends Resource
{
    protected static ?string $model = Cotizacion::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Ventas / POS';

    protected static ?string $navigationLabel = 'Cotizaciones';

    protected static ?string $modelLabel = 'Cotización';

    protected static ?string $pluralModelLabel = 'Cotizaciones';

    protected static ?int $navigationSort = 6;

    // Muestra la cantidad total de cotizaciones a la par del nombre en el menú izquierdo
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
                        Forms\Components\Section::make('Información de la Cotización')
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('numero_referencia')
                                    ->label('N° de referencia')
                                    ->default(fn () => 'QT-' . now()->format('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT))
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\Select::make('cliente_id')
                                    ->label('Cliente')
                                    ->options(Cliente::pluck('nombre', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nombre')
                                            ->label('Nombre / Razón Social')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('nit')
                                            ->label('NIT')
                                            ->default('CF')
                                            ->maxLength(50),
                                        Forms\Components\TextInput::make('telefono')
                                            ->label('Teléfono')
                                            ->tel()
                                            ->maxLength(50),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Correo Electrónico')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('direccion')
                                            ->label('Dirección')
                                            ->default('Ciudad')
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->createOptionUsing(fn (array $data): int => Cliente::create($data)->id)
                                    ->createOptionAction(fn (Forms\Components\Actions\Action $action) => $action
                                        ->modalHeading('Crear Nuevo Cliente')
                                        ->modalButton('Guardar Cliente')
                                        ->modalWidth('lg')
                                    ),

                                Forms\Components\DatePicker::make('fecha_emision')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('estado')
                                    ->label('Estado')
                                    ->options([
                                        'PENDIENTE' => 'Pendiente',
                                        'APROBADA' => 'Aprobada',
                                        'FACTURADA' => 'Facturada',
                                        'ANULADA' => 'Anulada',
                                    ])
                                    ->default('PENDIENTE')
                                    ->required(),

                                Forms\Components\Hidden::make('sucursal_id')
                                    ->default(fn () => session('sucursal_activa_id') ?? Sucursal::first()?->id)
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\Hidden::make('fecha_vencimiento')
                                    ->default(now()->addDays(15))
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\Hidden::make('user_id')
                                    ->default(fn () => auth()->id())
                                    ->dehydrated(),
                            ]),

                        Forms\Components\Section::make('Artículos')
                            ->schema([
                                Forms\Components\Repeater::make('detalles')
                                    ->relationship('detalles')
                                    ->schema([
                                        Forms\Components\Select::make('producto_id')
                                            ->label('Artículo / Producto')
                                            ->options(function (Get $get) {
                                                $sucursalId = $get('../../sucursal_id');
                                                if (! $sucursalId) return [];

                                                return Producto::where('activo', true)
                                                    ->where('tipo', 'BIEN')
                                                    ->get()
                                                    ->mapWithKeys(fn ($p) => [$p->id => "{$p->nombre} (Precio: Q{$p->precio_venta})"]);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                if (! $state) return;
                                                $prod = Producto::find($state);
                                                if ($prod) {
                                                    $set('precio_unitario', (float) $prod->precio_venta);
                                                    $cant = (float) ($get('cantidad') ?? 1);
                                                    $set('subtotal', round($cant * (float) $prod->precio_venta, 2));
                                                }
                                                self::recalcularTotales($get, $set);
                                            })
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('cantidad')
                                            ->label('Cantidad')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->live(debounce: 250)
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                $cant = (float) $state;
                                                $precio = (float) ($get('precio_unitario') ?? 0);
                                                $set('subtotal', round($cant * $precio, 2));
                                                self::recalcularTotales($get, $set);
                                            })
                                            ->columnSpan(['md' => 2]),

                                        Forms\Components\TextInput::make('precio_unitario')
                                            ->label('Precio Unitario')
                                            ->numeric()
                                            ->prefix('Q')
                                            ->required()
                                            ->live(debounce: 250)
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                $precio = (float) $state;
                                                $cant = (float) ($get('cantidad') ?? 1);
                                                $set('subtotal', round($cant * $precio, 2));
                                                self::recalcularTotales($get, $set);
                                            })
                                            ->columnSpan(['md' => 3]),

                                        Forms\Components\TextInput::make('subtotal')
                                            ->label('Total')
                                            ->numeric()
                                            ->prefix('Q')
                                            ->readOnly()
                                            ->dehydrated()
                                            ->columnSpan(['md' => 3]),

                                        Forms\Components\TextInput::make('descripcion')
                                            ->label('Descripción opcional')
                                            ->placeholder('Detalle...')
                                            ->columnSpan(['md' => 4]),
                                    ])
                                    ->columns(12)
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcularTotales($get, $set))
                                    ->defaultItems(1)
                                    ->addActionLabel('+ Añadir artículo'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 8]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Resumen')
                            ->schema([
                                Forms\Components\Placeholder::make('total_articulos_display')
                                    ->label('Total de artículos')
                                    ->content(function (Get $get): string {
                                        $detalles = $get('detalles') ?? [];
                                        $totalQty = 0;
                                        foreach ($detalles as $det) {
                                            $totalQty += (float) ($det['cantidad'] ?? 0);
                                        }
                                        return (string) $totalQty;
                                    }),

                                Forms\Components\TextInput::make('descuento')
                                    ->label('Descuento')
                                    ->placeholder('10 o 10%')
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcularTotales($get, $set)),

                                Forms\Components\TextInput::make('costo_total')
                                    ->label('Gran total')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->extraInputAttributes(['class' => 'text-xl font-bold text-primary-600']),

                                Forms\Components\Textarea::make('observaciones')
                                    ->label('Nota')
                                    ->placeholder('Ingrese cualquier nota adicional...'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 4]),
            ])
            ->columns(12);
    }

    public static function recalcularTotales(Get $get, Set $set): void
    {
        $detalles = $get('../../detalles') ?? $get('detalles') ?? [];
        $subtotalGeneral = 0;

        foreach ($detalles as $det) {
            $sub = (float) ($det['subtotal'] ?? 0);
            $subtotalGeneral += $sub;
        }

        $descuentoInput = trim((string) ($get('../../descuento') ?? $get('descuento') ?? '0'));
        $montoDescuento = 0;

        if (str_ends_with($descuentoInput, '%')) {
            $porcentaje = (float) str_replace('%', '', $descuentoInput);
            $montoDescuento = $subtotalGeneral * ($porcentaje / 100);
        } else {
            $montoDescuento = (float) $descuentoInput;
        }

        $granTotal = max(0, $subtotalGeneral - $montoDescuento);

        $set('../../subtotal_general', round($subtotalGeneral, 2));
        $set('subtotal_general', round($subtotalGeneral, 2));
        
        $set('../../costo_total', round($granTotal, 2));
        $set('costo_total', round($granTotal, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_referencia')
                    ->label('N° de referencia')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('fecha_emision')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('costo_total')
                    ->label('Gran Total')
                    ->money('GTQ')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDIENTE' => 'warning',
                        'APROBADA' => 'info',
                        'FACTURADA' => 'success',
                        'EXPIRADA', 'ANULADA' => 'danger',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'PENDIENTE' => 'Pendiente',
                        'APROBADA' => 'Aprobada',
                        'FACTURADA' => 'Facturada',
                        'ANULADA' => 'Anulada',
                    ]),

                Tables\Filters\Filter::make('fecha_emision')
                    ->form([
                        Forms\Components\DatePicker::make('desde')->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_emision', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_emision', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('')
                    ->tooltip('Imprimir / Descargar PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Cotizacion $record) => route('cotizaciones.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('aprobar')
                    ->label('')
                    ->tooltip('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Cotizacion $record) => $record->estado === 'PENDIENTE')
                    ->action(function (Cotizacion $record) {
                        $record->update(['estado' => 'APROBADA']);
                        Notification::make()->title('Cotización aprobada exitosamente')->success()->send();
                    }),

                Tables\Actions\ViewAction::make()->label('')->tooltip('Ver'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Eliminar'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCotizacions::route('/'),
            'create' => Pages\CreateCotizacion::route('/create'),
            'edit' => Pages\EditCotizacion::route('/{record}/edit'),
            'view' => Pages\ViewCotizacion::route('/{record}'),
        ];
    }
}