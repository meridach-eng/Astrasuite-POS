<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarSucursalActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // 1. Si no hay sesión iniciada, continuar
        if (! $user) {
            return $next($request);
        }

        // 2. Permitir peticiones internas de Livewire (vital para componentes reactivos)
        if ($request->is('livewire/*')) {
            return $next($request);
        }

        // 3. Permitir rutas de autenticación y la propia pantalla de selección
        if ($request->routeIs('filament.admin.pages.seleccionar-sucursal') || $request->routeIs('filament.admin.auth.*')) {
            return $next($request);
        }

        // 4. El Super Admin NO debe ser bloqueado si aún no hay sucursales o si está configurando
        if ($user->esSuperAdmin()) {
            return $next($request);
        }

        // 5. Para usuarios operativos (cajeros): forzar selección de sucursal
        if (! session()->has('sucursal_activa_id')) {
            return redirect()->route('filament.admin.pages.seleccionar-sucursal');
        }

        return $next($request);
    }
}