<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages;
use App\Filament\Resources\ProductoResource\RelationManagers\LotesRelationManager;
use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Sucursal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as PhpSpreadsheetDate;

class ProductoResource extends Resource
{
    protected static ?string $model = Producto::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Catálogo / Inventario';

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

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
                        Forms\Components\Section::make('Información Básica')
                            ->columns(3)
                            ->schema([
                                Forms\Components\Select::make('tipo')
                                    ->label('Tipo de Producto')
                                    ->options([
                                        'BIEN' => 'Bien (Inventariable)',
                                        'SERVICIO' => 'Servicio',
                                    ])
                                    ->default('BIEN')
                                    ->required()
                                    ->live(),

                                Forms\Components\TextInput::make('nombre')
                                    ->label('Nombre del Producto')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('codigo_interno')
                                    ->label('Código Interno / SKU')
                                    ->placeholder('Ej. LAC-001')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('codigo_barras')
                                    ->label('Código de Barras')
                                    ->placeholder('Escanear o ingresar código')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50),

                                Forms\Components\Select::make('categoria_id')
                                    ->label('Categoría')
                                    ->relationship('categoria', 'nombre')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nombre')->required(),
                                    ]),

                                Forms\Components\Select::make('proveedor_id')
                                    ->label('Proveedor')
                                    ->relationship('proveedor', 'nombre')
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nombre')
                                            ->label('Nombre o Razón Social')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('nit')
                                            ->label('NIT o Documento')
                                            ->placeholder('Ej. 7483920-1 o CF')
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('telefono')
                                            ->label('Teléfono / WhatsApp')
                                            ->tel()
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('contacto')
                                            ->label('Nombre de Contacto')
                                            ->placeholder('Ej. Carlos Mendoza')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('email')
                                            ->label('Correo Electrónico')
                                            ->email()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('direccion')
                                            ->label('Dirección')
                                            ->default('Ciudad')
                                            ->maxLength(255),

                                        Forms\Components\Hidden::make('activo')
                                            ->default(true),
                                    ])
                                    ->createOptionModalHeading('Registrar Nuevo Proveedor'),

                                Forms\Components\Textarea::make('descripcion')
                                    ->label('Descripción')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Información de Precios y Utilidad')
                            ->columns(4)
                            ->schema([
                                Forms\Components\TextInput::make('precio_compra')
                                    ->label('Precio Compra (Costo AVCO)')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->default(0)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $costo = (float) $state;
                                        $margen = (float) $get('margen_utilidad');
                                        if ($costo > 0 && $margen > 0) {
                                            $set('precio_venta', round($costo + ($costo * ($margen / 100)), 2));
                                        }
                                    }),

                                Forms\Components\TextInput::make('margen_utilidad')
                                    ->label('Margen Utilidad (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(20)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $margen = (float) $state;
                                        $costo = (float) $get('precio_compra');
                                        if ($costo > 0) {
                                            $set('precio_venta', round($costo + ($costo * ($margen / 100)), 2));
                                        }
                                    }),

                                Forms\Components\TextInput::make('precio_venta')
                                    ->label('Precio Venta Público')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $venta = (float) $state;
                                        $costo = (float) $get('precio_compra');
                                        if ($costo > 0 && $venta > $costo) {
                                            $set('margen_utilidad', round((($venta - $costo) / $costo) * 100, 2));
                                        }
                                    }),

                                Forms\Components\TextInput::make('precio_mayorista')
                                    ->label('Precio Mayorista')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->nullable(),
                            ]),

                        Forms\Components\Section::make('Existencias Físicas por Sucursal')
                            ->description(fn (string $operation) => $operation === 'create'
                                ? 'Establece las existencias iniciales para cada una de las sucursales.'
                                : 'Existencias auditadas actuales. Este valor solo se modifica mediante compras, ventas, traslados o ajustes.')
                            ->visible(fn (Get $get) => $get('tipo') === 'BIEN')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema(function () {
                                        return Sucursal::where('activo', true)->get()->map(function (Sucursal $sucursal) {
                                            return Forms\Components\TextInput::make("stock_sucursal_{$sucursal->id}")
                                                ->label("Stock en {$sucursal->nombre}")
                                                ->numeric()
                                                ->default(0)
                                                ->suffix('unid.')
                                                ->disabled(fn (string $operation) => $operation === 'edit');
                                        })->toArray();
                                    }),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Control de Inventario y Lotificación')
                            ->schema([
                                Forms\Components\TextInput::make('stock_minimo')
                                    ->label('Stock Mínimo General')
                                    ->numeric()
                                    ->default(5)
                                    ->visible(fn (Get $get) => $get('tipo') === 'BIEN'),

                                Forms\Components\Toggle::make('maneja_lotes')
                                    ->label('Maneja Lotes / Perecederos')
                                    ->helperText('Activa rotación PEPS/FEFO y fechas de caducidad.')
                                    ->visible(fn (Get $get) => $get('tipo') === 'BIEN')
                                    ->disabled(fn (string $operation) => $operation === 'edit')
                                    ->default(false),

                                Forms\Components\Toggle::make('activo')
                                    ->label('Producto Activo')
                                    ->default(true),
                            ]),

                        Forms\Components\Section::make('Imagen del Producto')
                            ->schema([
                                Forms\Components\FileUpload::make('imagen')
                                    ->image()
                                    ->disk('public')
                                    ->directory('productos')
                                    ->visibility('public')
                                    ->maxSize(2048)
                                    ->alignCenter(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        $sucursalActivaId = session('sucursal_activa_id');

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')
                    ->label('Foto')
                    ->circular()
                    ->disk('public'),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Producto $record) => "SKU: {$record->codigo_interno}" . ($record->codigo_barras ? " | Barcode: {$record->codigo_barras}" : '')),

                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('precio_compra')
                    ->label('Costo (AVCO)')
                    ->money('GTQ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('precio_venta')
                    ->label('Precio Venta')
                    ->money('GTQ')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('stock_sucursal_actual')
                    ->label('Stock Actual')
                    ->state(function (Producto $record) use ($sucursalActivaId) {
                        if ($record->tipo === 'SERVICIO') {
                            return 'Servicio';
                        }
                        return $record->stockEnSucursal($sucursalActivaId) . ' unid.';
                    })
                    ->badge()
                    ->color(function (Producto $record) use ($sucursalActivaId) {
                        if ($record->tipo === 'SERVICIO') {
                            return 'info';
                        }
                        $stock = $record->stockEnSucursal($sucursalActivaId);
                        return $stock <= $record->stock_minimo ? 'danger' : 'success';
                    }),

                Tables\Columns\TextColumn::make('stock_total')
                    ->label('Total Red')
                    ->state(function (Producto $record) {
                        if ($record->tipo === 'SERVICIO') {
                            return '-';
                        }
                        return $record->sucursales()->sum('stock_actual') . ' unid.';
                    })
                    ->color('gray'),

                Tables\Columns\ToggleColumn::make('activo')
                    ->label('Activo'),
            ])
            ->headerActions([
                // 1. Plantilla con columnas de lotes y sucursales añadidas
                Tables\Actions\Action::make('descargar_plantilla')
                    ->label('Descargar Plantilla Excel')
                    ->color('gray')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function () {
                        $sucursales = Sucursal::where('activo', true)->get();
                        
                        return Excel::download(new class($sucursales) implements FromArray, WithHeadings, WithStyles {
                            protected $sucursales;

                            public function __construct($sucursales) {
                                $this->sucursales = $sucursales;
                            }

                            public function array(): array {
                                $filaEjemplo = [
                                    'Jugo del Valle 3 Lt', 'Bebidas', 'PROD-001', '785541236', 'Bebida de durazno de 3 litros', 
                                    12.75, 25.49, 16.00, 15.00, 5, 1, 
                                    1, 'LOTE-2026-001', '2026-12-31'
                                ];
                                foreach ($this->sucursales as $s) {
                                    $filaEjemplo[] = 25; 
                                }
                                return [$filaEjemplo];
                            }

                            public function headings(): array {
                                $headings = [
                                    'nombre', 'categoria', 'codigo_interno', 'codigo_barras', 'descripcion', 
                                    'precio_compra', 'margen_utilidad', 'precio_venta', 'precio_mayorista', 
                                    'stock_minimo', 'activo', 'maneja_lotes', 'numero_lote', 'fecha_vencimiento'
                                ];
                                foreach ($this->sucursales as $s) {
                                    $headings[] = 'stock_sucursal_' . $s->id;
                                }
                                return $headings;
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
                        }, 'plantilla_productos_inventario.xlsx');
                    }),

                // 2. Importación optimizada para leer lotes, vencimientos y stocks por sucursal
                Tables\Actions\Action::make('importar_excel')
                    ->label('Importar Excel / Stock / Lotes')
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

                            array_shift($rows); // Fila 1: Encabezados
                            $sucursalesActivas = Sucursal::where('activo', true)->get();
                            $sucursalDefaultId = $sucursalesActivas->first()?->id ?? 1;

                            $importados = 0;
                            foreach ($rows as $row) {
                                $nombre = $row[0] ?? null;
                                if (!$nombre) continue;

                                $nombreCategoria = trim($row[1] ?? '');
                                $categoriaId = null;

                                if (!empty($nombreCategoria)) {
                                    $categoria = Categoria::firstOrCreate(['nombre' => $nombreCategoria]);
                                    $categoriaId = $categoria->id;
                                }

                                $codigoInterno = $row[2] ?? null;
                                $codigoBarras = $row[3] ?? null;
                                $descripcion = $row[4] ?? null;
                                $precioCompra = $row[5] ?? 0;
                                $margenUtilidad = $row[6] ?? 20;
                                $precioVenta = $row[7] ?? 0;
                                $precioMayorista = $row[8] ?? null;
                                $stockMinimo = $row[9] ?? 5;
                                $activo = isset($row[10]) ? (bool)$row[10] : true;
                                $manejaLotes = isset($row[11]) ? (bool)$row[11] : false;
                                $numeroLote = trim($row[12] ?? '');
                                
                                // Procesar fecha de vencimiento (maneja formato Excel numérico o texto Y-m-d)
                                $fechaVencimientoRaw = $row[13] ?? null;
                                $fechaVencimiento = null;
                                if (!empty($fechaVencimientoRaw)) {
                                    if (is_numeric($fechaVencimientoRaw)) {
                                        $fechaVencimiento = PhpSpreadsheetDate::excelToDateTimeObject($fechaVencimientoRaw)->format('Y-m-d');
                                    } else {
                                        $parsedDate = date_create($fechaVencimientoRaw);
                                        $fechaVencimiento = $parsedDate ? $parsedDate->format('Y-m-d') : null;
                                    }
                                }

                                // 1. Crear o actualizar el producto maestro
                                $producto = Producto::updateOrCreate(
                                    ['codigo_interno' => $codigoInterno],
                                    [
                                        'nombre' => $nombre,
                                        'categoria_id' => $categoriaId,
                                        'codigo_barras' => $codigoBarras,
                                        'descripcion' => $descripcion,
                                        'precio_compra' => $precioCompra,
                                        'margen_utilidad' => $margenUtilidad,
                                        'precio_venta' => $precioVenta,
                                        'precio_mayorista' => $precioMayorista,
                                        'stock_minimo' => $stockMinimo,
                                        'maneja_lotes' => $manejaLotes,
                                        'activo' => $activo,
                                    ]
                                );

                                // 2. Procesar existencias por sucursal a partir de la columna O (índice 14) en adelante
                                foreach ($sucursalesActivas as $index => $sucursal) {
                                    $colIndex = 14 + $index; 
                                    if (array_key_exists($colIndex, $row) && is_numeric($row[$colIndex])) {
                                        $stockFisico = (int)$row[$colIndex];

                                        $producto->sucursales()->syncWithoutDetaching([
                                            $sucursal->id => ['stock_actual' => $stockFisico]
                                        ]);

                                        // 3. Si el producto maneja lotes, tiene stock mayor a 0 y se indicó un número de lote, registrar/actualizar el lote
                                        if ($manejaLotes && $stockFisico > 0 && !empty($numeroLote)) {
                                            Lote::updateOrCreate(
                                                [
                                                    'producto_id' => $producto->id,
                                                    'sucursal_id' => $sucursal->id,
                                                    'numero_lote' => $numeroLote,
                                                ],
                                                [
                                                    'fecha_vencimiento' => $fechaVencimiento,
                                                    'cantidad_inicial' => $stockFisico,
                                                    'cantidad_actual' => $stockFisico,
                                                    'costo_unitario' => $precioCompra,
                                                    'activo' => true,
                                                ]
                                            );
                                        }
                                    }
                                }

                                $importados++;
                            }

                            Notification::make()
                                ->title("¡Importación exitosa! Se procesaron {$importados} productos, lotes y existencias.")
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
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'BIEN' => 'Bienes',
                        'SERVICIO' => 'Servicios',
                    ]),
                Tables\Filters\SelectFilter::make('categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'nombre'),
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado'),
            ])
            ->actions([
                Tables\Actions\Action::make('ver_existencias')
                    ->label('Sucursales')
                    ->icon('heroicon-m-building-storefront')
                    ->color('info')
                    ->visible(fn (Producto $record) => $record->tipo === 'BIEN')
                    ->modalHeading(fn (Producto $record) => "Existencias por Sucursal: {$record->nombre}")
                    ->infolist([
                        Infolists\Components\RepeatableEntry::make('sucursales')
                            ->label('Distribución en Sucursales')
                            ->schema([
                                Infolists\Components\TextEntry::make('nombre')
                                    ->label('Sucursal')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('pivot.stock_actual')
                                    ->label('Existencias')
                                    ->badge()
                                    ->color('success')
                                    ->suffix(' unidades'),
                            ])
                            ->grid(2),
                    ]),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            LotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductos::route('/'),
            'create' => Pages\CreateProducto::route('/create'),
            'edit' => Pages\EditProducto::route('/{record}/edit'),
        ];
    }
}