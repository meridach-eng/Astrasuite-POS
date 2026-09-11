<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventarioResource\Pages;
use App\Models\Producto;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventarioResource extends Resource
{
    protected static ?string $model = Producto::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Catálogo / Inventario';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $modelLabel = 'Inventario';

    protected static ?string $pluralModelLabel = 'Inventarios';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Si tienes una sucursal activa en la sesión, podemos asegurar que cargue la relación pivot correspondiente
                $sucursalId = session('sucursal_activa_id');
                if ($sucursalId) {
                    $query->with(['sucursales' => fn ($q) => $q->where('sucursal_id', $sucursalId)]);
                } else {
                    $query->with('sucursales');
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Artículo / Código')
                    ->description(fn (Producto $record) => $record->codigo_interno ? "({$record->codigo_interno})" : null)
                    ->searchable(['nombre', 'codigo_interno', 'codigo_barras'])
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_actual')
                    ->label('Total Inventario Cantidad')
                    ->getStateUsing(function (Producto $record) {
                        $sucursalId = session('sucursal_activa_id');
                        $stock = $sucursalId 
                            ? $record->stockEnSucursal($sucursalId) 
                            : $record->sucursales->sum('pivot.stock_actual');
                        
                        return number_format($stock, 2) . ' Unidad';
                    })
                    ->badge()
                    ->color(function (Producto $record): string {
                        $sucursalId = session('sucursal_activa_id');
                        $stock = $sucursalId 
                            ? $record->stockEnSucursal($sucursalId) 
                            : $record->sucursales->sum('pivot.stock_actual');
                        
                        $minimo = $sucursalId
                            ? ($record->sucursales->where('id', $sucursalId)->first()?->pivot->stock_minimo_sucursal ?? $record->stock_minimo)
                            : $record->stock_minimo;

                        return $stock <= $minimo ? 'danger' : 'success';
                    }),

                Tables\Columns\TextColumn::make('precio_venta')
                    ->label('Precio Venta / LPP')
                    ->money('GTQ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_valor')
                    ->label('Total')
                    ->getStateUsing(function (Producto $record) {
                        $sucursalId = session('sucursal_activa_id');
                        $stock = $sucursalId 
                            ? $record->stockEnSucursal($sucursalId) 
                            : $record->sucursales->sum('pivot.stock_actual');

                        return 'Q' . number_format($stock * $record->precio_venta, 2);
                    }),
            ])
            ->defaultSort('nombre', 'asc')
            ->filters([
                Tables\Filters\Filter::make('bajo_stock')
                    ->label('Solo productos con bajo stock')
                    ->query(function (Builder $query): Builder {
                        $sucursalId = session('sucursal_activa_id');
                        if ($sucursalId) {
                            return $query->whereHas('sucursales', function ($q) use ($sucursalId) {
                                $q->where('sucursal_id', $sucursalId)
                                  ->whereColumn('producto_sucursal.stock_actual', '<=', 'producto_sucursal.stock_minimo_sucursal');
                            });
                        }
                        return $query;
                    }),

                Tables\Filters\SelectFilter::make('categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'nombre')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventarios::route('/'),
        ];
    }
}