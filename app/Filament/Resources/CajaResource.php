<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CajaResource\Pages;
use App\Models\Caja;
use App\Models\Sucursal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CajaResource extends Resource
{
    protected static ?string $model = Caja::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationGroup = 'Ventas / POS';

    protected static ?string $navigationLabel = 'Puntos de Caja';

    protected static ?string $modelLabel = 'Caja';

    protected static ?string $pluralModelLabel = 'Cajas';

    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        $sucursalActivaId = session('sucursal_activa_id');
        $esSuperAdmin = auth()->user()?->esSuperAdmin() ?? false;

        return $form
            ->schema([
                Forms\Components\Section::make('Detalle de Caja')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('sucursal_id')
                            ->label('Sucursal')
                            ->relationship('sucursal', 'nombre')
                            ->default($sucursalActivaId)
                            ->disabled(! $esSuperAdmin)
                            ->dehydrated()
                            ->required(),

                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre de la Caja')
                            ->placeholder('Ej. Caja 1 - Mostrador')
                            ->required(),

                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->placeholder('Ej. POS-01'),

                        Forms\Components\Toggle::make('activo')
                            ->label('Habilitada')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sucursal.nombre')->label('Sucursal')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('nombre')->label('Caja')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('codigo')->label('Código'),
                Tables\Columns\IconColumn::make('activo')->label('Activo')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $sucursalActivaId = session('sucursal_activa_id');

        if (! auth()->user()?->esSuperAdmin() && $sucursalActivaId) {
            $query->where('sucursal_id', $sucursalActivaId);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCajas::route('/'),
            'create' => Pages\CreateCaja::route('/create'),
            'edit' => Pages\EditCaja::route('/{record}/edit'),
        ];
    }
}