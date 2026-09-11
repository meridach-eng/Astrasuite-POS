<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SesionCajaResource\Pages;
use App\Models\Caja;
use App\Models\SesionCaja;
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

class SesionCajaResource extends Resource
{
    protected static ?string $model = SesionCaja::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationGroup = 'Ventas / POS';

    protected static ?string $navigationLabel = 'Turnos de Caja';

    protected static ?string $modelLabel = 'Turno / Sesión';

    protected static ?string $pluralModelLabel = 'Turnos de Caja';

    public static function canCreate(): bool
    {
        // Opcional: Permitir crear solo si NO tiene sesión abierta, 
        // o dejarlo abierto para que el aviso de CreateSesionCaja le indique el motivo exacto.
        return true; 
    }

    public static function form(Form $form): Form
    {
        $sucursalActivaId = session('sucursal_activa_id');

        return $form
            ->schema([
                Forms\Components\Section::make('Apertura de Turno')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('caja_id')
                            ->label('Caja')
                            ->options(function () use ($sucursalActivaId) {
                                $query = Caja::query()->where('activo', true);
                                if (! auth()->user()?->esSuperAdmin() && $sucursalActivaId) {
                                    $query->where('sucursal_id', $sucursalActivaId);
                                }
                                return $query->pluck('nombre', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (?SesionCaja $record) => $record !== null),

                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => auth()->id())
                            ->dehydrated(),

                        Forms\Components\DateTimePicker::make('fecha_apertura')
                            ->label('Fecha y Hora de Apertura')
                            ->default(now())
                            ->required()
                            ->disabled(fn (?SesionCaja $record) => $record !== null),

                        Forms\Components\TextInput::make('monto_apertura')
                            ->label('Fondo Inicial (Q)')
                            ->numeric()
                            ->prefix('Q')
                            ->default(0)
                            ->required()
                            ->disabled(fn (?SesionCaja $record) => $record !== null),

                        Forms\Components\TextInput::make('estado')
                            ->label('Estado')
                            ->default('ABIERTA')
                            ->readOnly()
                            ->dehydrated(),
                    ]),

                Forms\Components\Section::make('Datos de Cierre y Arqueo')
                    ->columns(3)
                    ->visible(fn (?SesionCaja $record) => $record && $record->estado === 'CERRADA')
                    ->schema([
                        Forms\Components\DateTimePicker::make('fecha_cierre')
                            ->label('Fecha de Cierre')
                            ->readOnly(),

                        Forms\Components\TextInput::make('monto_esperado')
                            ->label('Efectivo Esperado (Q)')
                            ->numeric()
                            ->prefix('Q')
                            ->readOnly(),

                        Forms\Components\TextInput::make('monto_real')
                            ->label('Efectivo Entregado (Q)')
                            ->numeric()
                            ->prefix('Q')
                            ->readOnly(),

                        Forms\Components\TextInput::make('diferencia')
                            ->label('Diferencia (+/-)')
                            ->numeric()
                            ->prefix('Q')
                            ->readOnly(),

                        Forms\Components\Textarea::make('observaciones')
                            ->label('Arqueo Integral e Informes de Cierre')
                            ->readOnly()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('caja.sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable(),

                Tables\Columns\TextColumn::make('caja.nombre')
                    ->label('Caja')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cajero'),

                Tables\Columns\TextColumn::make('fecha_apertura')
                    ->label('Apertura')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_cierre')
                    ->label('Cierre')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('En curso...'),

                Tables\Columns\TextColumn::make('monto_apertura')
                    ->label('Fondo')
                    ->money('GTQ'),

                Tables\Columns\TextColumn::make('monto_esperado')
                    ->label('Esperado')
                    ->money('GTQ')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('monto_real')
                    ->label('Entregado')
                    ->money('GTQ')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('diferencia')
                    ->label('Diferencia')
                    ->money('GTQ')
                    ->placeholder('-')
                    ->color(function ($state) {
                        if (is_null($state)) return 'gray';
                        $val = (float) $state;
                        if ($val < 0) return 'danger';
                        if ($val > 0) return 'success';
                        return 'gray';
                    })
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => $state === 'ABIERTA' ? 'success' : 'gray'),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                // ACCIÓN CERRAR CAJA (INTEGRAL 4 MÉTODOS)
                Tables\Actions\Action::make('cerrar_caja')
                    ->label('Cerrar Caja')
                    ->icon('heroicon-o-lock-closed')
                    ->button()
                    ->color('danger')
                    ->visible(fn (SesionCaja $record) => $record->estado === 'ABIERTA')
                    ->modalHeading(fn (SesionCaja $record) => "Cuadre Integral de Turno #{$record->id} - {$record->caja->nombre}")
                    ->modalWidth('3xl')
                    ->form(function (SesionCaja $record) {
                        $desglose = $record->desgloseMetodosCobro();
                        $efectivoVentas = (float) ($desglose['EFECTIVO'] ?? 0);
                        $tarjetaVentas = (float) ($desglose['TARJETA'] ?? 0);
                        $transferenciaVentas = (float) ($desglose['TRANSFERENCIA'] ?? 0);
                        $chequeVentas = (float) ($desglose['CHEQUE'] ?? 0);

                        $fondoInicial = (float) $record->monto_apertura;
                        $efectivoEsperado = round($fondoInicial + $efectivoVentas, 2);
                        $tarjetaEsperado = round($tarjetaVentas, 2);
                        $transferenciaEsperado = round($transferenciaVentas, 2);
                        $chequeEsperado = round($chequeVentas, 2);

                        return [
                            Forms\Components\Section::make('1. Efectivo en Gaveta')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Placeholder::make('info_efectivo')
                                        ->label('Desglose Sistema')
                                        ->content("Fondo: Q{$fondoInicial} | Cobros: Q{$efectivoVentas}"),

                                    Forms\Components\Hidden::make('monto_esperado')
                                        ->default($efectivoEsperado),

                                    Forms\Components\TextInput::make('monto_real')
                                        ->label('Efectivo Físico Contado')
                                        ->numeric()
                                        ->prefix('Q')
                                        ->default($efectivoEsperado)
                                        ->required()
                                        ->live(debounce: 250)
                                        ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                            $esp = (float) $get('monto_esperado');
                                            $contado = (float) $state;
                                            $set('dif_efectivo', round($contado - $esp, 2));
                                        }),

                                    Forms\Components\TextInput::make('dif_efectivo')
                                        ->label('Dif. Efectivo')
                                        ->numeric()
                                        ->prefix('Q')
                                        ->default(0)
                                        ->readOnly()
                                        ->dehydrated(false),
                                ]),

                            Forms\Components\Section::make('2. Vouchers de Tarjeta (POS Bancario)')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Placeholder::make('info_tarjeta')
                                        ->label('Sistema')
                                        ->content("Esperado Lote: Q{$tarjetaEsperado}"),

                                    Forms\Components\Hidden::make('tarjeta_esperado')
                                        ->default($tarjetaEsperado),

                                    Forms\Components\TextInput::make('tarjeta_real')
                                        ->label('Suma Total Vouchers')
                                        ->numeric()
                                        ->prefix('Q')
                                        ->default($tarjetaEsperado)
                                        ->required()
                                        ->live(debounce: 250)
                                        ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                            $esp = (float) $get('tarjeta_esperado');
                                            $contado = (float) $state;
                                            $set('dif_tarjeta', round($contado - $esp, 2));
                                        }),

                                    Forms\Components\TextInput::make('dif_tarjeta')
                                        ->label('Dif. Tarjetas')
                                        ->numeric()
                                        ->prefix('Q')
                                        ->default(0)
                                        ->readOnly()
                                        ->dehydrated(false),
                                ]),

                            Forms\Components\Section::make('3. Boletas de Transferencia / Depósito')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Placeholder::make('info_transf')
                                        ->label('Sistema')
                                        ->content("Esperado Depósitos: Q{$transferenciaEsperado}"),

                                    Forms\Components\Hidden::make('transferencia_esperado')
                                        ->default($transferenciaEsperado),

                                    Forms\Components\TextInput::make('transferencia_real')
                                        ->label('Suma Total Boletas')
                                        ->numeric()
                                        ->prefix('Q')
                                        ->default($transferenciaEsperado)
                                        ->required()
                                        ->live(debounce: 250)
                                        ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                            $esp = (float) $get('transferencia_esperado');
                                            $contado = (float) $state;
                                            $set('dif_transferencia', round($contado - $esp, 2));
                                        }),

                                    Forms\Components\TextInput::make('dif_transferencia')
                                        ->label('Dif. Transferencias')
                                        ->numeric()
                                        ->prefix('Q')
                                        ->default(0)
                                        ->readOnly()
                                        ->dehydrated(false),
                                ]),

                            Forms\Components\Section::make('4. Cheques Recibidos')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Placeholder::make('info_cheque')
                                        ->label('Sistema')
                                        ->content("Esperado Cheques: Q{$chequeEsperado}"),

                                    Forms\Components\Hidden::make('cheque_esperado')
                                        ->default($chequeEsperado),

                                    Forms\Components\TextInput::make('cheque_real')
                                        ->label('Suma Total Cheques')
                                        ->numeric()
                                        ->prefix('Q')
                                        ->default($chequeEsperado)
                                        ->required()
                                        ->live(debounce: 250)
                                        ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                            $esp = (float) $get('cheque_esperado');
                                            $contado = (float) $state;
                                            $set('dif_cheque', round($contado - $esp, 2));
                                        }),

                                    Forms\Components\TextInput::make('dif_cheque')
                                        ->label('Dif. Cheques')
                                        ->numeric()
                                        ->prefix('Q')
                                        ->default(0)
                                        ->readOnly()
                                        ->dehydrated(false),
                                ]),

                            Forms\Components\Section::make('Cierre General')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\DateTimePicker::make('fecha_cierre')
                                        ->label('Fecha y Hora Cierre')
                                        ->default(now())
                                        ->required(),

                                    Forms\Components\Textarea::make('observaciones')
                                        ->label('Notas / Justificación Integral')
                                        ->placeholder('Observaciones sobre efectivo, vouchers, boletas o cheques...')
                                        ->columnSpanFull(),
                                ]),
                        ];
                    })
                    ->action(function (SesionCaja $record, array $data) {
                        $efectivoEsp = (float) $data['monto_esperado'];
                        $efectivoReal = (float) $data['monto_real'];
                        $difEfectivo = round($efectivoReal - $efectivoEsp, 2);

                        $tarjetaEsp = (float) $data['tarjeta_esperado'];
                        $tarjetaReal = (float) $data['tarjeta_real'];
                        $difTarjeta = round($tarjetaReal - $tarjetaEsp, 2);

                        $transfEsp = (float) $data['transferencia_esperado'];
                        $transfReal = (float) $data['transferencia_real'];
                        $difTransf = round($transfReal - $transfEsp, 2);

                        $chequeEsp = (float) $data['cheque_esperado'];
                        $chequeReal = (float) $data['cheque_real'];
                        $difCheque = round($chequeReal - $chequeEsp, 2);

                        $auditoria = "ARQUEO INTEGRAL BACKEND:\n"
                            . "- Efectivo Gaveta: Esperado Q{$efectivoEsp} | Real Q{$efectivoReal} (Dif: Q{$difEfectivo})\n"
                            . "- Vouchers Tarjeta: Esperado Q{$tarjetaEsp} | Real Q{$tarjetaReal} (Dif: Q{$difTarjeta})\n"
                            . "- Boletas Transf: Esperado Q{$transfEsp} | Real Q{$transfReal} (Dif: Q{$difTransf})\n"
                            . "- Cheques: Esperado Q{$chequeEsp} | Real Q{$chequeReal} (Dif: Q{$difCheque})";

                        if (filled($data['observaciones'] ?? null)) {
                            $auditoria .= "\nNotas: " . trim($data['observaciones']);
                        }

                        $record->update([
                            'fecha_cierre' => $data['fecha_cierre'] ?? now(),
                            'monto_esperado' => $efectivoEsp,
                            'monto_real' => $efectivoReal,
                            'diferencia' => $difEfectivo,
                            'observaciones' => $auditoria,
                            'estado' => 'CERRADA',
                        ]);

                        Notification::make()
                            ->title('Turno Cerrado Correctamente')
                            ->body("Caja cerrada de forma integral.")
                            ->success()
                            ->send();
                    }),

                // ACCIÓN VER ARQUEO
                Tables\Actions\Action::make('ver_arqueo')
                    ->label('Arqueo')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('gray')
                    ->visible(fn (SesionCaja $record) => $record->estado === 'CERRADA')
                    ->modalHeading(fn (SesionCaja $record) => "Auditoría de Arqueo: Turno #{$record->id}")
                    ->infolist([
                        Infolists\Components\Section::make('Tiempos y Responsable')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('caja.nombre')->label('Caja'),
                                Infolists\Components\TextEntry::make('user.name')->label('Cajero'),
                                Infolists\Components\TextEntry::make('caja.sucursal.nombre')->label('Sucursal'),
                                Infolists\Components\TextEntry::make('fecha_apertura')->label('Apertura')->dateTime('d/m/Y H:i'),
                                Infolists\Components\TextEntry::make('fecha_cierre')->label('Cierre')->dateTime('d/m/Y H:i'),
                                Infolists\Components\TextEntry::make('estado')->label('Estado')->badge()->color('gray'),
                            ]),

                        Infolists\Components\Section::make('Conciliación Financiera')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('monto_apertura')->label('Fondo Inicial')->money('GTQ'),
                                Infolists\Components\TextEntry::make('monto_esperado')->label('Efectivo Esperado')->money('GTQ'),
                                Infolists\Components\TextEntry::make('monto_real')->label('Efectivo Entregado')->money('GTQ')->weight('bold'),
                                Infolists\Components\TextEntry::make('diferencia')
                                    ->label('Diferencia Efectivo')
                                    ->money('GTQ')
                                    ->color(fn ($state) => (float) $state < 0 ? 'danger' : ((float) $state > 0 ? 'success' : 'gray'))
                                    ->weight('black'),
                                Infolists\Components\TextEntry::make('observaciones')->label('Detalle Integral y Notas')->columnSpanFull()->placeholder('Sin observaciones'),
                            ]),
                    ]),

                Tables\Actions\EditAction::make(),
            ])
            ->actionsPosition(Tables\Enums\ActionsPosition::AfterColumns);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $sucursalActivaId = session('sucursal_activa_id');

        if (! auth()->user()?->esSuperAdmin() && $sucursalActivaId) {
            $query->whereHas('caja', fn ($q) => $q->where('sucursal_id', $sucursalActivaId));
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSesionCajas::route('/'),
            'create' => Pages\CreateSesionCaja::route('/create'),
            'edit' => Pages\EditSesionCaja::route('/{record}/edit'),
        ];
    }
}