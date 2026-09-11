<?php

namespace App\Filament\Resources\SesionCajaResource\Pages;

use App\Filament\Resources\SesionCajaResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSesionCaja extends EditRecord
{
    protected static string $resource = SesionCajaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cerrar_caja_header')
                ->label('Cerrar y Arquear Caja')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->visible(fn () => $this->record->estado === 'ABIERTA')
                ->modalHeading(fn () => "Cuadre Integral de Turno #{$this->record->id} - {$this->record->caja->nombre}")
                ->modalWidth('3xl')
                ->form(function () {
                    $record = $this->record;
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
                ->action(function (array $data) {
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

                    $this->record->update([
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

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}