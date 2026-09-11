<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionNegocio;
use App\Models\VentaCuota;
use Barryvdh\DomPDF\Facade\Pdf;

class ContratoCuotaPdfController extends Controller
{
    public function __invoke($id)
    {
        $ventaCuota = VentaCuota::with([
            'cliente', 
            'sucursal', 
            'detalles.producto', 
            'cuotas', 
            'user'
        ])->findOrFail($id);

        $negocio = ConfiguracionNegocio::first();

        // Resolución del logo a Base64 para el PDF
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

        $pdf = Pdf::loadView('pdf.contrato-cuotas', compact('ventaCuota', 'negocio', 'logoBase64'));
        $pdf->setPaper('a4', 'portrait');

        // download() obliga al navegador a descargar el archivo directamente
        return $pdf->download('Contrato_' . $ventaCuota->numero_referencia . '.pdf');
    }
}