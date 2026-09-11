<?php

namespace App\Filament\Resources\CompraResource\Pages;

use App\Filament\Resources\CompraResource;
use App\Models\Compra;
use App\Models\PagoCompra;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewCompra extends ViewRecord
{
    protected static string $resource = CompraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('registrar_abono_header')
                ->label('Registrar Abono')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->visible(fn () => $this->record->estado_recepcion !== 'ANULADO' && (float) $this->record->saldo_pendiente > 0)
                ->modalHeading(fn () => "Registrar Abono para {$this->record->numero_referencia}")
                ->modalDescription(fn () => "Saldo pendiente actual: Q" . number_format($this->record->saldo_pendiente, 2))
                ->form([
                    Forms\Components\TextInput::make('monto')
                        ->label('Monto a Abonar (Q)')
                        ->numeric()
                        ->prefix('Q')
                        ->default(fn () => (float) $this->record->saldo_pendiente)
                        ->minValue(0.01)
                        ->maxValue(fn () => (float) $this->record->saldo_pendiente)
                        ->required(),

                    Forms\Components\Select::make('metodo_pago')
                        ->label('Método de Pago')
                        ->options([
                            'EFECTIVO' => 'Efectivo',
                            'TRANSFERENCIA' => 'Transferencia / Banco',
                            'TARJETA' => 'Tarjeta',
                            'CHEQUE' => 'Cheque',
                            'OTRO' => 'Otro',
                        ])
                        ->default('EFECTIVO')
                        ->required(),

                    Forms\Components\TextInput::make('referencia_pago')
                        ->label('No. Boleta / Referencia / Cheque')
                        ->placeholder('Ej. Boleta No. 894120'),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        $compra = $this->record;
                        $montoAbono = (float) $data['monto'];

                        PagoCompra::create([
                            'compra_id' => $compra->id,
                            'metodo_pago' => $data['metodo_pago'],
                            'monto' => $montoAbono,
                            'referencia_pago' => $data['referencia_pago'] ?? null,
                        ]);

                        $nuevoMontoPagado = round((float) $compra->monto_pagado + $montoAbono, 2);
                        $nuevoSaldo = max(0, round((float) $compra->total - $nuevoMontoPagado, 2));
                        $nuevoEstadoPago = $nuevoSaldo <= 0 ? 'PAGADO' : 'PARCIAL';

                        $compra->update([
                            'monto_pagado' => $nuevoMontoPagado,
                            'saldo_pendiente' => $nuevoSaldo,
                            'estado_pago' => $nuevoEstadoPago,
                        ]);
                    });

                    Notification::make()
                        ->title('Abono Registrado')
                        ->body('El pago fue aplicado y la factura actualizada con éxito.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['monto_pagado', 'saldo_pendiente', 'estado_pago']);
                }),
        ];
    }
}