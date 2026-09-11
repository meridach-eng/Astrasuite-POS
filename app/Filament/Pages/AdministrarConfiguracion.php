<?php

namespace App\Filament\Pages;

use App\Models\ConfiguracionNegocio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AdministrarConfiguracion extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Datos del Negocio y FEL';

    protected static ?string $title = 'Configuración General del Negocio';

    protected static string $view = 'filament.pages.administrar-configuracion';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasRole('super_admin') || $user->hasRole('admin'));
    }

    public function mount(): void
    {
        $this->cargarDatos();
    }

    public function cargarDatos(): void
    {
        $registro = ConfiguracionNegocio::getRegistro();
        $this->form->fill($registro->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identidad y Datos Fiscales')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('nombre_comercial')
                            ->label('Nombre Comercial')
                            ->required(),

                        Forms\Components\TextInput::make('razon_social')
                            ->label('Razón Social')
                            ->required(),

                        Forms\Components\TextInput::make('nit')
                            ->label('NIT del Negocio')
                            ->required(),

                        Forms\Components\Select::make('regimen_impuestos')
                            ->label('Régimen de Impuestos (Guatemala)')
                            ->options([
                                'GENERAL_12' => 'Régimen General del IVA (12%)',
                                'PEQUENO_5' => 'Pequeño Contribuyente (5%)',
                                'PEQUENO_ELECTRONICO_4' => 'Pequeño Contribuyente Electrónico (4%)',
                                'EXENTO' => 'Exento de Impuestos',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('telefono')->label('Teléfono Fijo'),
                        Forms\Components\TextInput::make('whatsapp')->label('WhatsApp / Celular'),
                        Forms\Components\TextInput::make('email')->label('Correo')->email(),
                        Forms\Components\TextInput::make('sitio_web')->label('Sitio Web')->prefix('https://'),
                        Forms\Components\Textarea::make('direccion')->label('Dirección Fiscal')->columnSpanFull(),
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            ->directory('negocio')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Integración Felplex FEL')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('fel_habilitado')
                            ->label('Habilitar Facturación Felplex')
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('felplex_id')
                            ->label('Felplex ID / Cuenta')
                            ->visible(fn (Get $get) => (bool) $get('fel_habilitado'))
                            ->required(fn (Get $get) => (bool) $get('fel_habilitado')),

                        Forms\Components\TextInput::make('felplex_url')
                            ->label('API URL')
                            ->default('https://api.felplex.com')
                            ->visible(fn (Get $get) => (bool) $get('fel_habilitado'))
                            ->required(fn (Get $get) => (bool) $get('fel_habilitado')),

                        Forms\Components\TextInput::make('felplex_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => (bool) $get('fel_habilitado'))
                            ->required(fn (Get $get) => (bool) $get('fel_habilitado')),
                    ]),
            ])
            ->statePath('data');
    }

    public function guardar(): void
    {
        $datos = $this->form->getState();
        $registro = ConfiguracionNegocio::getRegistro();
        $registro->update($datos);

        Notification::make()
            ->title('Configuración guardada correctamente')
            ->success()
            ->send();
    }

    public function cancelar(): void
    {
        $this->redirect(route('filament.admin.pages.dashboard'));
    }
}