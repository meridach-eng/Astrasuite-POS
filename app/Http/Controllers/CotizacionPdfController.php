<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\ConfiguracionNegocio;

class CotizacionPdfController extends Controller
{
    public function __invoke(Cotizacion $cotizacion)
    {
        $cotizacion->load(['cliente', 'sucursal', 'detalles.producto', 'user']);
        
        $negocio = ConfiguracionNegocio::first();

        $logoBase64 = null;

        // 1. Intentar resolver con el campo logo de la base de datos
        if ($negocio && !empty($negocio->logo)) {
            $nombreLimpio = ltrim(str_replace(['public/', 'storage/'], '', $negocio->logo), '/');
            $candidatas = [
                storage_path('app/public/' . $nombreLimpio),
                storage_path('app/' . $nombreLimpio),
                public_path('storage/' . $nombreLimpio),
            ];

            foreach ($candidatas as $ruta) {
                if (file_exists($ruta) && !is_dir($ruta)) {
                    $contenido = file_get_contents($ruta);
                    $mime = mime_content_type($ruta) ?: 'image/png';
                    $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($contenido);
                    break;
                }
            }
        }

        // 2. Fallback de seguridad: buscar directamente el archivo en storage/app/public/negocio/
        if (!$logoBase64) {
            $carpetaNegocio = storage_path('app/public/negocio');
            if (is_dir($carpetaNegocio)) {
                $archivos = glob($carpetaNegocio . '/*.{png,jpg,jpeg,webp,PNG,JPG,JPEG,WEBP}', GLOB_BRACE);
                if (!empty($archivos)) {
                    $rutaArchivo = $archivos[0];
                    $contenido = file_get_contents($rutaArchivo);
                    $mime = mime_content_type($rutaArchivo) ?: 'image/png';
                    $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($contenido);
                }
            }
        }

        return view('pdf.cotizacion', compact('cotizacion', 'negocio', 'logoBase64'));
    }
}