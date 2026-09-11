<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GastoCategoriaResource\Pages;
use App\Models\GastoCategoria;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GastoCategoriaResource extends Resource
{
    protected static ?string $model = GastoCategoria::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Compras y Proveedores';
    protected static ?string $navigationLabel = 'Categorías de Gastos';
    protected static ?string $modelLabel = 'Categoría de Gasto';
    protected static ?string $pluralModelLabel = 'Categorías de Gastos';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre de Categoría')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('descripcion')
                ->label('Descripción')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('descripcion')->label('Descripción')->limit(50),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->date('d/m/Y'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGastoCategorias::route('/'),
            'create' => Pages\CreateGastoCategoria::route('/create'),
            'edit' => Pages\EditGastoCategoria::route('/{record}/edit'),
        ];
    }
}