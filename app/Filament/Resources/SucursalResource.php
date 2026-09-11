<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SucursalResource\Pages;
use App\Models\Sucursal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SucursalResource extends Resource
{
    protected static ?string $model = Sucursal::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Sucursales';

    protected static ?string $modelLabel = 'Sucursal';

    protected static ?string $pluralModelLabel = 'Sucursales';

    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Sede')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('nombre')->label('Nombre de la Sucursal')->required(),
                        Forms\Components\TextInput::make('codigo_establecimiento_sat')
                            ->label('No. Establecimiento SAT')
                            ->helperText('Código para DTE en SAT / Felplex')
                            ->default('1')
                            ->required(),
                        Forms\Components\TextInput::make('telefono')->label('Teléfono'),
                        Forms\Components\TextInput::make('municipio')->label('Municipio'),
                        Forms\Components\TextInput::make('departamento')->label('Departamento'),
                        Forms\Components\Textarea::make('direccion')->label('Dirección')->columnSpanFull(),
                        Forms\Components\Toggle::make('activo')->label('Activa')->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->label('Sucursal')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('codigo_establecimiento_sat')->label('No. SAT')->sortable(),
                Tables\Columns\TextColumn::make('telefono')->label('Teléfono'),
                Tables\Columns\TextColumn::make('municipio')->label('Municipio'),
                Tables\Columns\IconColumn::make('activo')->label('Activa')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSucursals::route('/'),
            'create' => Pages\CreateSucursal::route('/create'),
            'edit' => Pages\EditSucursal::route('/{record}/edit'),
        ];
    }
}