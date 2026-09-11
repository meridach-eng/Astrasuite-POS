<?php

namespace App\Filament\Resources\SesionCajaResource\Pages;

use App\Filament\Resources\SesionCajaResource;
use App\Models\SesionCaja;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSesionCaja extends CreateRecord
{
    protected static string $resource = SesionCajaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validar si el usuario actual ya tiene una sesión abierta
        $sesionAbierta = SesionCaja::where('user_id', auth()->id())
            ->where('estado', 'ABIERTA')
            ->exists();

        if ($sesionAbierta) {
            Notification::make()
                ->title('Turno Duplicado Detectado')
                ->body('Ya tienes un turno de caja abierto. Debes cerrarlo antes de iniciar una nueva sesión.')
                ->danger()
                ->persistent()
                ->send();

            // Detiene la ejecución redirigiendo de vuelta al listado
            $this->halt();
        }

        return $data;
    }
}