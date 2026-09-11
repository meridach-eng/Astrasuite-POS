<?php

namespace App\Filament\Resources\ProductoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LotesRelationManager extends RelationManager
{
    protected static string $relationship = 'lotes';

    protected static ?string $title = 'Lotes y Fechas de Vencimiento (PEPS)';

    protected static ?string $modelLabel = 'Lote';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->tipo === 'BIEN' && (bool) $ownerRecord->maneja_lotes;
    }

    // Deshabilita la creación manual de lotes (solo compras/entradas)
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('numero_lote')
                    ->label('No. Lote')
                    ->disabled(),

                Forms\Components\DatePicker::make('fecha_vencimiento')
                    ->label('Fecha de Vencimiento')
                    ->required()
                    ->native(false),

                Forms\Components\TextInput::make('cantidad_inicial')
                    ->label('Cantidad Ingresada')
                    ->numeric()
                    ->disabled(),

                Forms\Components\TextInput::make('cantidad_actual')
                    ->label('Disponible')
                    ->numeric()
                    ->disabled(),

                Forms\Components\TextInput::make('costo_unitario')
                    ->label('Costo Unitario')
                    ->numeric()
                    ->prefix('Q')
                    ->disabled(),

                Forms\Components\Toggle::make('activo')
                    ->label('Lote Habilitado')
                    ->helperText('Desactivar para bloquear su venta en el POS por merma o retiro.')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_lote')
            ->defaultSort('fecha_vencimiento', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('numero_lote')
                    ->label('No. Lote')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => now()->diffInDays($state, false) <= 30 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('cantidad_actual')
                    ->label('Disponible')
                    ->numeric()
                    ->sortable()
                    ->suffix(' unid.'),

                Tables\Columns\TextColumn::make('costo_unitario')
                    ->label('Costo Unitario (PEPS)')
                    ->money('GTQ'),

                Tables\Columns\ToggleColumn::make('activo')
                    ->label('Activo'),
            ])
            ->headerActions([
                // Sin CreateAction: los lotes solo ingresan vía Compras
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Ajustar Vencimiento / Estado'),
            ])
            ->bulkActions([]);
    }
}