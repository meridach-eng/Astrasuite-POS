<?php

namespace App\Filament\Pages;

use App\Models\Sucursal;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class SeleccionarSucursal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static string $view = 'filament.pages.seleccionar-sucursal';

    protected static ?string $title = 'Seleccionar Sucursal de Trabajo';

    protected static ?string $navigationLabel = 'Cambiar Sucursal';

    protected static ?int $navigationSort = -1;

    public function getSucursalesProperty(): Collection
    {
        $user = auth()->user();

        if ($user->esSuperAdmin()) {
            return Sucursal::query()->where('activo', true)->get();
        }

        return $user->sucursales()->where('activo', true)->get();
    }

    public function ingresarSucursal(int $sucursalId): void
    {
        $sucursal = Sucursal::find($sucursalId);

        if (! $sucursal) {
            Notification::make()->title('Sucursal no encontrada')->danger()->send();
            return;
        }

        session(['sucursal_activa_id' => $sucursal->id]);
        session(['sucursal_activa_nombre' => $sucursal->nombre]);

        Notification::make()
            ->title("Operando en: {$sucursal->nombre}")
            ->success()
            ->send();

        $this->redirect('/admin');
    }
}