<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use App\Services\FelplexService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Ventas / POS';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static ?int $navigationSort = 3;

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
                        Forms\Components\Section::make('Datos de Facturación (FEL)')
                            ->description('Datos requeridos para la emisión de facturas electrónicas.')
                            ->columns(3)
                            ->schema([
                                Forms\Components\Select::make('tipo_documento')
                                    ->label('Tipo Doc.')
                                    ->options([
                                        'NIT' => 'NIT',
                                        'CUI' => 'CUI / DPI',
                                        'PASAPORTE' => 'Pasaporte',
                                    ])
                                    ->default('NIT')
                                    ->required(),

                                Forms\Components\TextInput::make('numero_documento')
                                    ->label('No. Identificación / NIT')
                                    ->placeholder('Ej. 1234567-8 o CUI')
                                    ->default('CF')
                                    ->required()
                                    ->maxLength(30)
                                    ->suffixAction(
                                        FormAction::make('consultar_nit')
                                            ->icon('heroicon-m-magnifying-glass')
                                            ->tooltip('Consultar NIT o CUI en la SAT')
                                            ->action(function (Forms\Set $set, $state) {
                                                if (empty(trim($state)) || strtoupper(trim($state)) === 'CF') {
                                                    Notification::make()->title('Aviso')->body('Ingresa un NIT o CUI válido para consultar.')->warning()->send();
                                                    return;
                                                }

                                                $felService = new FelplexService();
                                                $resultado = $felService->buscarNitOCui($state);

                                                if ($resultado['success']) {
                                                    $set('nombre', $resultado['nombre']);
                                                    if (isset($resultado['tipo']) && $resultado['tipo'] === 'CUI') {
                                                        $set('tipo_documento', 'CUI');
                                                    } else {
                                                        $set('tipo_documento', 'NIT');
                                                    }

                                                    if (!empty($resultado['direccion'])) {
                                                        $set('direccion', $resultado['direccion']);
                                                    }

                                                    Notification::make()
                                                        ->title("{$resultado['tipo']} Encontrado")
                                                        ->body("Contribuyente: {$resultado['nombre']}")
                                                        ->success()
                                                        ->send();
                                                } elseif (isset($resultado['no_registrado']) && $resultado['no_registrado'] === true) {
                                                    if (isset($resultado['tipo']) && $resultado['tipo'] === 'CUI') {
                                                        $set('tipo_documento', 'CUI');
                                                    } else {
                                                        $set('tipo_documento', 'NIT');
                                                    }

                                                    Notification::make()
                                                        ->title('Documento Válido')
                                                        ->body('No se encontró en caché de FELplex, puedes ingresar el nombre y dirección manualmente.')
                                                        ->info()
                                                        ->send();
                                                } else {
                                                    Notification::make()
                                                        ->title('Sin resultados')
                                                        ->body($resultado['error'])
                                                        ->warning()
                                                        ->send();
                                                }
                                            })
                                    ),

                                Forms\Components\TextInput::make('nombre')
                                    ->label('Nombre o Razón Social')
                                    ->placeholder('Nombre registrado en SAT')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('nombre_comercial')
                                    ->label('Nombre Comercial / Apodo')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('direccion')
                                    ->label('Dirección Fiscal')
                                    ->default('CIUDAD')
                                    ->required()
                                    ->columnSpan(2)
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Section::make('Contacto y Crédito')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('telefono')
                                    ->label('Teléfono / WhatsApp')
                                    ->tel()
                                    ->maxLength(20),

                                Forms\Components\TextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->email()
                                    ->helperText('Para envío facturas y documentos')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('limite_credito')
                                    ->label('Límite de Crédito (Q)')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->default(0),

                                Forms\Components\TextInput::make('dias_credito')
                                    ->label('Días de Crédito')
                                    ->numeric()
                                    ->suffix('días')
                                    ->default(0),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Estado')
                            ->schema([
                                Forms\Components\Toggle::make('activo')
                                    ->label('Cliente Habilitado')
                                    ->helperText('Si se deshabilita, no aparecerá disponible para cobros en el POS.')
                                    ->default(true),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_documento')
                    ->label('NIT / CUI')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre o Razón Social')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('limite_credito')
                    ->label('Crédito')
                    ->money('GTQ')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('activo')
                    ->label('Activo'),
            ])
            ->headerActions([
                // 1. Descargar Plantilla Excel Estilizada
                Tables\Actions\Action::make('descargar_plantilla')
                    ->label('Descargar Plantilla Excel')
                    ->color('gray')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function () {
                        return Excel::download(new class implements FromArray, WithHeadings, WithStyles {
                            public function array(): array {
                                return [
                                    ['NIT', '1234567-8', 'Distribuidora del Norte S.A.', 'Norte Comercial', 'Zona 4, Ciudad', '5555-1234', 'ventas@norte.com', 5000.00, 30, 1]
                                ];
                            }
                            public function headings(): array {
                                return [
                                    'tipo_documento', 
                                    'numero_documento', 
                                    'nombre', 
                                    'nombre_comercial', 
                                    'direccion', 
                                    'telefono', 
                                    'email', 
                                    'limite_credito', 
                                    'dias_credito', 
                                    'activo'
                                ];
                            }
                            public function styles(Worksheet $sheet): ?array {
                                return [
                                    1 => [
                                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                                        'fill' => [
                                            'fillType' => Fill::FILL_SOLID,
                                            'startColor' => ['rgb' => '1B6CA8'],
                                        ],
                                    ],
                                ];
                            }
                        }, 'plantilla_clientes.xlsx');
                    }),

                // 2. Importación Masiva de Excel
                Tables\Actions\Action::make('importar_excel')
                    ->label('Importar Excel')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-up')
                    ->form([
                        Forms\Components\FileUpload::make('archivo_excel')
                            ->label('Seleccionar Archivo Excel (.xlsx, .xls)')
                            ->disk('local')
                            ->directory('temp-imports')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $filePath = storage_path('app/private/' . $data['archivo_excel']);
                        if (!file_exists($filePath)) {
                            $filePath = storage_path('app/' . $data['archivo_excel']);
                        }

                        try {
                            $spreadsheet = IOFactory::load($filePath);
                            $sheet = $spreadsheet->getActiveSheet();
                            $rows = $sheet->toArray();

                            if (empty($rows) || count($rows) <= 1) {
                                Notification::make()->title('El archivo Excel está vacío')->danger()->send();
                                return;
                            }

                            array_shift($rows);
                            $importados = 0;

                            foreach ($rows as $row) {
                                $numeroDocumento = $row[1] ?? 'CF';
                                $nombre = $row[2] ?? null;
                                if (!$nombre) continue;

                                Cliente::updateOrCreate(
                                    ['numero_documento' => $numeroDocumento],
                                    [
                                        'tipo_documento' => $row[0] ?? 'NIT',
                                        'numero_documento' => $numeroDocumento,
                                        'nombre' => $nombre,
                                        'nombre_comercial' => $row[3] ?? null,
                                        'direccion' => $row[4] ?? 'CIUDAD',
                                        'telefono' => $row[5] ?? null,
                                        'email' => $row[6] ?? null,
                                        'limite_credito' => $row[7] ?? 0,
                                        'dias_credito' => $row[8] ?? 0,
                                        'activo' => isset($row[9]) ? (bool)$row[9] : true,
                                    ]
                                );
                                $importados++;
                            }

                            Notification::make()
                                ->title("¡Importación exitosa! Se procesaron {$importados} clientes.")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al procesar el archivo: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Inactivos')
                    ->native(false),
            ])
            ->actions([
                // ACCIÓN 1: Enviar WhatsApp al cliente
                Tables\Actions\Action::make('enviar_whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->tooltip('Abrir conversación en WhatsApp')
                    ->visible(fn (Cliente $record): bool => ! empty($record->telefono))
                    ->url(function (Cliente $record): string {
                        // Limpiar caracteres no numéricos
                        $numero = preg_replace('/[^0-9]/', '', $record->telefono);

                        // Si es número local guatemalteco de 8 dígitos, anteponer código de país 502
                        if (strlen($numero) === 8) {
                            $numero = '502' . $numero;
                        }

                        $mensaje = rawurlencode("Hola {$record->nombre}, le saludamos con gusto.");

                        return "https://wa.me/{$numero}?text={$mensaje}";
                    }, shouldOpenInNewTab: true),

                // ACCIÓN 2: Enviar Correo desde el cliente predeterminado
                Tables\Actions\Action::make('enviar_correo')
                    ->label('Correo')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->tooltip('Redactar correo con la app predeterminada')
                    ->visible(fn (Cliente $record): bool => ! empty($record->email))
                    ->url(fn (Cliente $record): string => 'mailto:' . trim($record->email)),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}