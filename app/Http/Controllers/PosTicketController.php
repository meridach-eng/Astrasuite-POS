<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionNegocio;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PosTicketController extends Controller
{
    public function imprimir(Venta $venta)
    {
        $venta->load([
            'detalles.producto',
            'detalles.lote',
            'pagos',
            'cliente',
            'sucursal',
            'user',
            'sesionCaja.caja',
        ]);

        $config = null;
        if (class_exists(ConfiguracionNegocio::class)) {
            $config = ConfiguracionNegocio::first();
        }
        if (! $config && Schema::hasTable('configuracion_negocio')) {
            $config = DB::table('configuracion_negocio')->first();
        }

        $regimen = strtoupper((string) ($config->regimen_impuestos ?? ($config->regimen_tributario ?? 'GENERAL_12')));
        $etiquetaRegimen = 'Régimen General del IVA (12%)';
        if (str_contains($regimen, 'PEQUENO_5') || str_contains($regimen, '5')) {
            $etiquetaRegimen = 'Pequeño Contribuyente (5%)';
        } elseif (str_contains($regimen, '4') || str_contains($regimen, 'ELECTR')) {
            $etiquetaRegimen = 'Pequeño Contribuyente Electrónico (4%)';
        } elseif (str_contains($regimen, 'EXENTO') || $regimen === '0') {
            $etiquetaRegimen = 'Exento de Impuestos';
        }

        return view('tickets.pos-80mm', [
            'venta' => $venta,
            'config' => $config,
            'etiquetaRegimen' => $etiquetaRegimen,
        ]);
    }
}