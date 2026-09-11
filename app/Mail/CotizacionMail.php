<?php

namespace App\Mail;

use App\Models\Cotizacion;
use App\Models\ConfiguracionNegocio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CotizacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Cotizacion $cotizacion,
        public ?string $mensajePersonalizado = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cotización N° ' . $this->cotizacion->numero_referencia,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cotizacion',
        );
    }

    public function attachments(): array
    {
        $this->cotizacion->load(['cliente', 'sucursal', 'detalles.producto', 'user']);
        $negocio = ConfiguracionNegocio::first();

        $logoBase64 = null;
        
        // Verificamos si existe el registro y la columna logo tiene valor
        if ($negocio && !empty($negocio->logo)) {
            $logoPath = $negocio->logo; // Ej: "logos/imagen.png"

            // Método oficial y seguro de Laravel para discos públicos
            if (Storage::disk('public')->exists($logoPath)) {
                $fileContent = Storage::disk('public')->get($logoPath);
                $mimeType = Storage::disk('public')->mimeType($logoPath);
                $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($fileContent);
            } 
            // Respaldo por si la ruta incluye 'storage/' o está en public directo
            else {
                $absolutePath = public_path('storage/' . ltrim($logoPath, '/'));
                if (file_exists($absolutePath)) {
                    $type = pathinfo($absolutePath, PATHINFO_EXTENSION);
                    $fileContent = file_get_contents($absolutePath);
                    $logoBase64 = 'data:image/' . strtolower($type) . ';base64,' . base64_encode($fileContent);
                }
            }
        }

        // Limpiar búfer para evitar bloqueos
        if (ob_get_level()) {
            ob_end_clean();
        }

        $pdf = Pdf::loadView('pdf.cotizacion', [
            'cotizacion' => $this->cotizacion,
            'negocio' => $negocio,
            'logoBase64' => $logoBase64,
        ])->setPaper('letter', 'portrait');

        return [
            Attachment::fromData(
                fn () => $pdf->output(), 
                'Cotizacion-' . $this->cotizacion->numero_referencia . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}