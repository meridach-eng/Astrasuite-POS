<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\ConfiguracionNegocio;
use Illuminate\Support\Facades\Http;
use Exception;

class FelplexService
{
    protected ?string $baseUrl;
    protected ?string $apiKey;
    protected ?string $entityId;

    public function __construct()
    {
        $config = ConfiguracionNegocio::first();

        // Leemos desde la base de datos configurada en el panel, con respaldo en el .env
        $this->baseUrl = $config->felplex_url ?? config('services.felplex.url', 'https://api.stage.plex.lat');
        $this->apiKey = $config->felplex_api_key ?? config('services.felplex.key');
        $this->entityId = $config->felplex_id ?? config('services.felplex.id');
    }

    /**
     * Certifica una venta del POS hacia FELplex
     */
    public function certificarVentaPos(Venta $venta): array
    {
        try {
            if (empty($this->apiKey) || empty($this->entityId)) {
                return [
                    'success' => false,
                    'error' => 'Las credenciales de FELplex (ID o API Key) no están configuradas en el sistema.',
                ];
            }

            // 1. Construir los items basados en los detalles de la venta
            $items = [];
            foreach ($venta->detalles as $detalle) {
                $items[] = [
                    "qty" => (string) $detalle->cantidad,
                    "type" => $detalle->producto->tipo === 'SERVICIO' ? "S" : "B",
                    "price" => (float) $detalle->precio_unitario,
                    "description" => $detalle->producto->nombre,
                    "without_iva" => 0,
                    "discount" => (float) ($detalle->descuento ?? 0),
                    "is_discount_percentage" => 0,
                    "taxes" => [
                        "quantity" => null,
                        "tax_code" => null,
                        "full_name" => null,
                        "short_name" => null,
                        "tax_amount" => null,
                        "taxable_amount" => null
                    ]
                ];
            }

            // 2. Determinar si es Consumidor Final (CF)
            $esCf = empty($venta->cliente->numero_documento) || strtoupper($venta->cliente->numero_documento) === 'CF';
            $nitCliente = $esCf ? 'CF' : trim($venta->cliente->numero_documento);
            $nombreCliente = $esCf ? 'CF' : $venta->cliente->nombre;

            // 3. Estructurar el Payload exacto que exige FELplex
            $payload = [
                "type" => $venta->tipo_dte ?? "FACT",
                "currency" => "GTQ",
                "datetime_issue" => $venta->fecha_venta ? $venta->fecha_venta->format('Y-m-d\TH:i:s') : now()->format('Y-m-d\TH:i:s'),
                "external_id" => (string) $venta->numero_ticket,
                "items" => $items,
                "total" => (float) $venta->total,
                "total_tax" => (float) $venta->impuesto,
                "emails" => [
                    [
                        "email" => $venta->cliente->email ?? "facturacion@inexistent.com"
                    ]
                ],
                "to_cf" => $esCf ? 1 : 0,
                "to" => [
                    "tax_code_type" => "NIT",
                    "tax_code" => $nitCliente,
                    "tax_name" => $nombreCliente,
                    "address" => [
                        "street" => $venta->cliente->direccion ?? "Guatemala",
                        "city" => "Guatemala",
                        "state" => "GT",
                        "zip" => "01001",
                        "country" => "GT"
                    ]
                ],
                "exempt_phrase" => null,
                "custom_fields" => [
                    [
                        "name" => "Cajero",
                        "value" => $venta->user->name ?? "POS"
                    ],
                    [
                        "name" => "Sucursal",
                        "value" => $venta->sucursal->nombre ?? "Principal"
                    ]
                ]
            ];

            // 4. Limpiar la URL base por si el usuario ingresó el endpoint completo por error
            $baseUrlClean = rtrim(explode('/api/', $this->baseUrl)[0], '/');
            $endpoint = "{$baseUrlClean}/api/entity/{$this->entityId}/invoices/await";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Authorization' => $this->apiKey,
            ])->post($endpoint, $payload);

            $resData = $response->json();

            if ($response->successful() && isset($resData['valid']) && $resData['valid'] === true) {
                return [
                    'success' => true,
                    'uuid' => $resData['uuid'] ?? null,
                    'authorization' => $resData['sat']['authorization'] ?? null,
                    'serie' => $resData['sat']['serie'] ?? null,
                    'numero' => $resData['sat']['no'] ?? null,
                    'invoice_url' => $resData['invoice_url'] ?? null,
                    'invoice_xml' => $resData['invoice_xml'] ?? null,
                    'data' => $resData,
                ];
            }

            return [
                'success' => false,
                'error' => $resData['errors'] ?? ($resData['message'] ?? 'Error desconocido al certificar en FELplex'),
                'status' => $response->status(),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Anula un DTE certificado ante FELplex mediante método DELETE
     */
    public function anularFactura(string $uuidFel, string $motivoAnulacion): array
    {
        try {
            if (empty($this->apiKey) || empty($this->entityId)) {
                return [
                    'success' => false,
                    'error' => 'Las credenciales de FELplex no están configuradas.',
                ];
            }

            $payload = [
                "reason" => $motivoAnulacion,
            ];

            $baseUrlClean = rtrim(explode('/api/', $this->baseUrl)[0], '/');
            $endpoint = "{$baseUrlClean}/api/entity/{$this->entityId}/invoices/{$uuidFel}";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Authorization' => $this->apiKey,
            ])->withBody(json_encode($payload), 'application/json')
              ->delete($endpoint);

            $resData = $response->json();

            if ($response->successful() && isset($resData['success']) && $resData['success'] === true) {
                return [
                    'success' => true,
                    'data' => $resData,
                ];
            }

            return [
                'success' => false,
                'error' => $resData['errors'] ?? ($resData['message'] ?? 'Error desconocido al anular el DTE en FELplex'),
                'status' => $response->status(),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Busca los datos fiscales de un NIT o CUI (DPI) en FELplex de forma automática
     */
    public function buscarNitOCui(string $identificacion): array
    {
        try {
            if (empty($this->apiKey) || empty($this->entityId)) {
                return [
                    'success' => false,
                    'error' => 'Las credenciales de FELplex no están configuradas.',
                ];
            }

            $limpio = trim($identificacion);

            if (empty($limpio) || strtoupper($limpio) === 'CF') {
                return [
                    'success' => false,
                    'error' => 'Ingresa un NIT o CUI válido para consultar.',
                ];
            }

            // Detectar automáticamente si es CUI (13 o 14 dígitos numéricos) o NIT
            $tipoBusqueda = (ctype_digit($limpio) && strlen($limpio) >= 13 && strlen($limpio) <= 14) ? 'CUI' : 'NIT';

            $baseUrlClean = rtrim(explode('/api/', $this->baseUrl)[0], '/');
            $endpoint = "{$baseUrlClean}/api/entity/{$this->entityId}/find/{$tipoBusqueda}/{$limpio}";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-Authorization' => $this->apiKey,
            ])->get($endpoint);

            $resData = $response->json();

            if ($response->successful() && !empty($resData)) {
                $dataCliente = is_array($resData) && isset($resData[0]) ? $resData[0] : $resData;

                return [
                    'success' => true,
                    'tipo' => $tipoBusqueda,
                    'nombre' => $dataCliente['tax_name'] ?? ($dataCliente['name'] ?? ''),
                    'direccion' => $dataCliente['address']['street'] ?? 'Guatemala',
                    'email' => $dataCliente['emails'][0] ?? '',
                    'data' => $dataCliente,
                ];
            }

            // Si no está registrado en el caché de FELplex, permitimos continuar de todas formas
            return [
                'success' => false,
                'tipo' => $tipoBusqueda,
                'no_registrado' => true,
                'error' => "El {$tipoBusqueda} no está registrado en FELplex. Puedes escribir el nombre manualmente.",
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Alias retrocompatible por si se llama desde otros controladores antiguos
     */
    public function buscarNit(string $nit): array
    {
        return $this->buscarNitOCui($nit);
    }
}