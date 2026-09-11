<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProveedorResource\Pages;
use App\Models\Proveedor;
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

class ProveedorResource extends Resource
{
    protected static ?string $model = Proveedor::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Compras y Proveedores';

    protected static ?string $navigationLabel = 'Proveedores';

    protected static ?string $modelLabel = 'Proveedor';

    protected static ?string $pluralModelLabel = 'Proveedores';

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
                        Forms\Components\Section::make('Datos del Proveedor')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('nit')
                                    ->label('NIT / CUI')
                                    ->default('CF')
                                    ->maxLength(20)
                                    ->suffixAction(
                                        FormAction::make('consultar_nit_proveedor')
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
                                                    if (!empty($resultado['direccion'])) {
                                                        $set('direccion', $resultado['direccion']);
                                                    }

                                                    Notification::make()
                                                        ->title("{$resultado['tipo']} Encontrado")
                                                        ->body("Proveedor: {$resultado['nombre']}")
                                                        ->success()
                                                        ->send();
                                                } elseif (isset($resultado['no_registrado']) && $resultado['no_registrado'] === true) {
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
                                    ->label('Razón Social / Nombre')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('contacto')
                                    ->label('Persona de Contacto')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('telefono')
                                    ->label('Teléfono / WhatsApp')
                                    ->tel()
                                    ->maxLength(20),

                                Forms\Components\TextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->email()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('direccion')
                                    ->label('Dirección Comercial')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Estado')
                            ->schema([
                                Forms\Components\Toggle::make('activo')
                                    ->label('Proveedor Activo')
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
                Tables\Columns\TextColumn::make('nit')
                    ->label('NIT / CUI')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('contacto')
                    ->label('Contacto')
                    ->searchable(),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                                    ['9876543-2', 'Distribuidora S.A.', 'Lic. Pérez', '2233-4455', 'ventas@distribuidora.com', 'Zona 4, Guatemala', 1]
                                ];
                            }
                            public function headings(): array {
                                return [
                                    'nit', 
                                    'nombre', 
                                    'contacto', 
                                    'telefono', 
                                    'email', 
                                    'direccion', 
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
                        }, 'plantilla_proveedores.xlsx');
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
                                $nit = $row[0] ?? 'CF';
                                $nombre = $row[1] ?? null;
                                if (!$nombre) continue;

                                Proveedor::updateOrCreate(
                                    ['nit' => $nit],
                                    [
                                        'nit' => $nit,
                                        'nombre' => $nombre,
                                        'contacto' => $row[2] ?? null,
                                        'telefono' => $row[3] ?? null,
                                        'email' => $row[4] ?? null,
                                        'direccion' => $row[5] ?? 'Ciudad',
                                        'activo' => isset($row[6]) ? (bool)$row[6] : true,
                                    ]
                                );
                                $importados++;
                            }

                            Notification::make()
                                ->title("¡Importación exitosa! Se procesaron {$importados} proveedores.")
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
                    ->label('Estado'),
            ])
            ->actions([
                // ACCIÓN 1: Enviar WhatsApp al proveedor
                Tables\Actions\Action::make('enviar_whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->tooltip('Abrir chat en WhatsApp')
                    ->visible(fn (Proveedor $record): bool => ! empty($record->telefono))
                    ->url(function (Proveedor $record): string {
                        $numero = preg_replace('/[^0-9]/', '', $record->telefono);

                        if (strlen($numero) === 8) {
                            $numero = '502' . $numero;
                        }

                        $destinatario = $record->contacto ?: $record->nombre;
                        $mensaje = rawurlencode("Hola {$destinatario}, le saludamos de compras.");

                        return "https://wa.me/{$numero}?text={$mensaje}";
                    }, shouldOpenInNewTab: true),

                // ACCIÓN 2: Enviar Correo con la app de correo predeterminada
                Tables\Actions\Action::make('enviar_correo')
                    ->label('Correo')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->tooltip('Redactar correo')
                    ->visible(fn (Proveedor $record): bool => ! empty($record->email))
                    ->url(fn (Proveedor $record): string => 'mailto:' . trim($record->email)),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProveedors::route('/'),
            'create' => Pages\CreateProveedor::route('/create'),
            'edit' => Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
}