<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Seguridad y Acceso';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        $esSuperAdmin = auth()->user()?->esSuperAdmin() ?? false;

        return $form
            ->schema([
                Forms\Components\Section::make('Datos del Usuario')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Completo')
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),

                        Forms\Components\Select::make('roles')
                            ->label('Rol del Sistema')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query) use ($esSuperAdmin) {
                                    // Si el usuario autenticado NO es super admin, ocultar la opción 'super_admin'
                                    if (! $esSuperAdmin) {
                                        $query->where('name', '!=', 'super_admin');
                                    }
                                }
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Sucursales Habilitadas')
                    ->description('Selecciona las sucursales donde este usuario tiene permiso de operar.')
                    ->schema([
                        Forms\Components\Select::make('sucursales')
                            ->label('Sucursales')
                            ->relationship('sucursales', 'nombre')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $esSuperAdmin = auth()->user()?->esSuperAdmin() ?? false;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TagsColumn::make('sucursales.nombre')
                    ->label('Sucursales')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TagsColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('info'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    // Si el registro a editar es un super_admin y quien edita no es super_admin, no permitirlo
                    ->visible(fn (User $record): bool => $esSuperAdmin || ! $record->hasRole('super_admin')),

                Tables\Actions\DeleteAction::make()
                    // No permitir eliminar a usuarios super_admin
                    ->visible(fn (User $record): bool => $esSuperAdmin && ! $record->hasRole('super_admin')),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Si el usuario autenticado no es super_admin, no ve a los usuarios con rol super_admin en el listado
        if ($user && ! $user->esSuperAdmin()) {
            $query->whereDoesntHave('roles', function (Builder $q) {
                $q->where('name', 'super_admin');
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}