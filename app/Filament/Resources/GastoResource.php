<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GastoResource\Pages;
use App\Models\Gasto;
use App\Models\GastoCategoria;
use App\Models\Proveedor;
use App\Models\Sucursal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GastoResource extends Resource
{
    protected static ?string $model = Gasto::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Compras y Proveedores';
    protected static ?string $navigationLabel = 'Gastos';
    protected static ?string $modelLabel = 'Gasto';
    protected static ?string $pluralModelLabel = 'Gastos';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del Gasto')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('numero_referencia')
                        ->label('N° de referencia')
                        ->default(fn () => 'GT-' . now()->format('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT))
                        ->required(),

                    Forms\Components\DatePicker::make('fecha')
                        ->label('Fecha')
                        ->default(now())
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('gasto_categoria_id')
                        ->label('Categoría Gasto')
                        ->options(GastoCategoria::pluck('nombre', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('nombre')->required()->maxLength(255),
                            Forms\Components\Textarea::make('descripcion')->columnSpanFull(),
                        ])
                        ->createOptionUsing(fn (array $data): int => GastoCategoria::create($data)->id),

                    Forms\Components\Select::make('proveedor_id')
                        ->label('Proveedor')
                        ->options(Proveedor::pluck('nombre', 'id'))
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
                        ->createOptionUsing(fn (array $data): int => Proveedor::create($data)->id)
                        ->createOptionAction(fn (Forms\Components\Actions\Action $action) => $action
                            ->modalHeading('Crear Nuevo Proveedor')
                            ->modalButton('Guardar Proveedor')
                            ->modalWidth('lg')
                        ),

                    Forms\Components\TextInput::make('monto')
                        ->label('Monto')
                        ->numeric()
                        ->prefix('Q')
                        ->required(),

                    Forms\Components\Select::make('metodo_pago')
                        ->label('Método de pago')
                        ->options([
                            'Efectivo' => 'Efectivo',
                            'Transferencia' => 'Transferencia',
                            'Tarjeta' => 'Tarjeta',
                            'Cheque' => 'Cheque',
                            'Crédito' => 'Crédito',
                        ])
                        ->default('Efectivo')
                        ->required(),

                    Forms\Components\Toggle::make('pagado')
                        ->label('Pagado')
                        ->helperText('Desmarque si el gasto queda pendiente (control de cuentas por pagar)')
                        ->default(true)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción / Nota')
                        ->placeholder('Detalle del gasto...')
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('sucursal_id')
                        ->default(fn () => session('sucursal_activa_id') ?? Sucursal::first()?->id),

                    Forms\Components\Hidden::make('user_id')
                        ->default(fn () => auth()->id()),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_referencia')
                    ->label('N° de referencia')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría Gasto')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money('GTQ')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('metodo_pago')
                    ->label('Método Pago'),

                Tables\Columns\IconColumn::make('pagado')
                    ->label('Pagado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('gasto_categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'nombre'),

                Tables\Filters\SelectFilter::make('proveedor_id')
                    ->label('Proveedor')
                    ->relationship('proveedor', 'nombre'),

                Tables\Filters\TernaryFilter::make('pagado')
                    ->label('Estado de Pago')
                    ->trueLabel('Pagados')
                    ->falseLabel('Pendientes'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGastos::route('/'),
            'create' => Pages\CreateGasto::route('/create'),
            'edit' => Pages\EditGasto::route('/{record}/edit'),
        ];
    }
}