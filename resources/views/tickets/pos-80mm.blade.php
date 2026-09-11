<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - {{ $venta->numero_ticket }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace;
            color: #000;
        }

        body {
            background-color: #fff;
            display: flex;
            justify-content: center;
            padding: 10px 0;
        }

        .ticket-wrapper {
            width: 76mm;
            max-width: 76mm;
            padding: 4mm 2mm;
            font-size: 11px;
            line-height: 1.25;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .empresa-nombre {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .double-divider {
            border-top: 2px dashed #000;
            margin: 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            font-size: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 3px;
        }

        table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .totales-tabla td {
            padding: 1px 0;
        }

        .total-destacado {
            font-size: 14px;
            font-weight: bold;
        }

        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            body {
                padding: 0;
                margin: 0;
            }

            .ticket-wrapper {
                width: 76mm;
                padding: 2mm 3mm;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body onload="iniciarImpresion()">

    <div class="ticket-wrapper">
        
        <!-- ENCABEZADO FISCAL (SIN LOGO) -->
        <div class="text-center">
            <div class="empresa-nombre uppercase">{{ $config->nombre_comercial ?? 'ASTRA SUITE POS' }}</div>
            <div>{{ $config->razon_social ?? 'ASTRA INGENIERÍA' }}</div>
            <div>NIT: <strong>{{ $config->nit ?? 'CF' }}</strong></div>
            <div>{{ $config->direccion ?? 'Guatemala' }}</div>
            @if (!empty($config->telefono))
                <div>TEL: {{ $config->telefono }}</div>
            @endif
            <div style="font-size: 10px; margin-top: 2px; font-weight: bold;">{{ $etiquetaRegimen }}</div>
        </div>

        <div class="divider"></div>

        <!-- DATOS DE EMISIÓN -->
        <div>
            <div><strong>TICKET:</strong> {{ $venta->numero_ticket }}</div>
            <div><strong>FECHA:</strong> {{ $venta->fecha_venta->format('d/m/Y H:i:s') }}</div>
            <div><strong>SUCURSAL:</strong> {{ $venta->sucursal->nombre ?? 'Principal' }}</div>
            <div><strong>CAJA:</strong> {{ $venta->sesionCaja->caja->nombre ?? 'Caja 1' }} | Turno #{{ $venta->sesion_caja_id }}</div>
            <div><strong>CAJERO:</strong> {{ $venta->user->name ?? 'Admin' }}</div>
            <div class="divider"></div>
            <div><strong>CLIENTE:</strong> {{ $venta->cliente->nombre }}</div>
            <div><strong>NIT/DOC:</strong> {{ $venta->cliente->numero_documento ?? $venta->cliente->nit ?? 'CF' }}</div>
            @if (!empty($venta->cliente->direccion) && $venta->cliente->direccion !== 'CIUDAD')
                <div><strong>DIR:</strong> {{ $venta->cliente->direccion }}</div>
            @endif
        </div>

        <div class="divider"></div>

        <!-- DETALLE DE ARTÍCULOS -->
        <table>
            <thead>
                <tr>
                    <th class="text-left" style="width: 14%;">CANT</th>
                    <th class="text-left" style="width: 50%;">DESCRIPCIÓN</th>
                    <th class="text-right" style="width: 16%;">P.U</th>
                    <th class="text-right" style="width: 20%;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($venta->detalles as $det)
                    <tr>
                        <td class="text-left font-bold">{{ number_format($det->cantidad, 0) }}</td>
                        <td class="text-left">
                            {{ $det->producto->nombre }}
                            @if ($det->lote)
                                <div style="font-size: 9px; color: #333;">Lote: {{ $det->lote->numero_lote }}</div>
                            @endif
                            @if ($det->descuento > 0)
                                <div style="font-size: 9px;">Desc: -{{ $det->descuento }}%</div>
                            @endif
                        </td>
                        <td class="text-right">Q{{ number_format($det->precio_unitario, 2) }}</td>
                        <td class="text-right font-bold">Q{{ number_format($det->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <!-- TOTALES Y DESGLOSE FISCAL -->
        <table class="totales-tabla">
            <tr>
                <td class="text-left">SUBTOTAL:</td>
                <td class="text-right">Q{{ number_format($venta->subtotal, 2) }}</td>
            </tr>
            @if ($venta->descuento > 0)
                <tr>
                    <td class="text-left">DESCUENTO:</td>
                    <td class="text-right">-Q{{ number_format($venta->descuento, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td class="text-left">{{ $etiquetaRegimen }}:</td>
                <td class="text-right">Q{{ number_format($venta->impuesto, 2) }}</td>
            </tr>
            <tr class="double-divider">
                <td class="text-left total-destacado" style="padding-top: 4px;">TOTAL A PAGAR:</td>
                <td class="text-right total-destacado" style="padding-top: 4px;">Q{{ number_format($venta->total, 2) }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <!-- MÉTODOS DE PAGO UTILIZADOS -->
        <table class="totales-tabla">
            @foreach ($venta->pagos as $pago)
                <tr>
                    <td class="text-left">PAGO {{ $pago->metodo_pago }}:</td>
                    <td class="text-right">Q{{ number_format($pago->monto, 2) }}</td>
                </tr>
                @if (!empty($pago->referencia_pago))
                    <tr>
                        <td colspan="2" style="font-size: 9px; padding-left: 10px;">Ref: {{ $pago->referencia_pago }}</td>
                    </tr>
                @endif
            @endforeach
            @if ($venta->cambio_entregado > 0)
                <tr>
                    <td class="text-left font-bold" style="padding-top: 3px;">CAMBIO / VUELTO:</td>
                    <td class="text-right font-bold" style="padding-top: 3px;">Q{{ number_format($venta->cambio_entregado, 2) }}</td>
                </tr>
            @endif
            @if ($venta->saldo_pendiente > 0)
                <tr>
                    <td class="text-left font-bold" style="padding-top: 3px;">SALDO PENDIENTE (CRÉDITO):</td>
                    <td class="text-right font-bold" style="padding-top: 3px;">Q{{ number_format($venta->saldo_pendiente, 2) }}</td>
                </tr>
            @endif
        </table>

        <div class="divider"></div>

        <!-- PIE DE TICKET -->
        <div class="text-center" style="margin-top: 6px; font-size: 10px;">
            <div>¡GRACIAS POR SU COMPRA!</div>
            <div style="font-size: 9px; margin-top: 3px;">Comprobante interno de venta sin valor fiscal FEL directo.</div>
            <div style="font-size: 8px; color: #555; margin-top: 4px;">Software POS: Astra Ingeniería</div>
        </div>

    </div>

    <script>
        function iniciarImpresion() {
            window.onafterprint = function() {
                window.close();
            };
            window.focus();
            window.print();
        }
    </script>
</body>
</html>