<?php

use App\Http\Controllers\CotizacionPdfController;
use App\Http\Controllers\PosTicketController;
use App\Models\ConfiguracionNegocio;
use App\Models\PagoCuotaCobro;
use App\Models\VentaCuota;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContratoCuotaPdfController;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['web', 'auth'])->group(function () {

    // 1. Ticket de Venta POS de Contado (80mm)
    Route::get('/pos/ticket/{venta}', [PosTicketController::class, 'imprimir'])
        ->name('pos.ticket.imprimir');

    // 2. Cotización en PDF / Impresión
    Route::get('/admin/cotizaciones/{cotizacion}/pdf', CotizacionPdfController::class)
        ->name('cotizaciones.pdf');

    // 3. Contrato formal A4 PDF (Venta a Cuotas)
    Route::get('/ventas-cuotas/pdf/{id}', ContratoCuotaPdfController::class)
        ->name('ventas-cuotas.pdf');    

    // 4. Recibo Térmico de Abono / Cobro de Cuota (80mm)
    Route::get('/cuota-cobros/imprimir/{id}', function ($id) {
        $pago = PagoCuotaCobro::with([
            'ventaCuota.sucursal',
            'ventaCuota.cliente',
            'user'
        ])->findOrFail($id);

        $negocio = ConfiguracionNegocio::first();

        // Resolución del logo a Base64
        $logoBase64 = null;
        $carpetaNegocio = storage_path('app/public/negocio');
        if (is_dir($carpetaNegocio)) {
            $archivos = glob($carpetaNegocio . '/*.{png,jpg,jpeg,webp,PNG,JPG,JPEG,WEBP}', GLOB_BRACE);
            if (!empty($archivos)) {
                $ruta = $archivos[0];
                $mime = mime_content_type($ruta) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($ruta));
            }
        }

        return view('impresiones.recibo-cobro', compact('pago', 'negocio', 'logoBase64'));
    })->name('cuotas.recibo.imprimir');

    // 4. Contrato Inicial de Venta a Cuotas / Pagaré (80mm)
    Route::get('/ventas-cuotas/imprimir/{id}', function ($id) {
        $ventaCuota = VentaCuota::with([
            'cliente', 
            'sucursal', 
            'detalles.producto', 
            'cuotas', 
            'user'
        ])->findOrFail($id);

        $negocio = ConfiguracionNegocio::first();

        // Resolución del logo a Base64
        $logoBase64 = null;
        $carpetaNegocio = storage_path('app/public/negocio');
        if (is_dir($carpetaNegocio)) {
            $archivos = glob($carpetaNegocio . '/*.{png,jpg,jpeg,webp,PNG,JPG,JPEG,WEBP}', GLOB_BRACE);
            if (!empty($archivos)) {
                $ruta = $archivos[0];
                $mime = mime_content_type($ruta) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($ruta));
            }
        }

        return view('impresiones.contrato-cuotas', compact('ventaCuota', 'negocio', 'logoBase64'));
    })->name('ventas-cuotas.imprimir');
});