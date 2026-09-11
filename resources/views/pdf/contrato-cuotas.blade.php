<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Venta a Cuotas - {{ $ventaCuota->numero_referencia }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 30px;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo {
            max-height: 160px;
            max-width: 480px;
        }
        .empresa-info {
            text-align: right;
            font-size: 10pt;
            color: #475569;
        }
        .titulo-doc {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .subtitulo-doc {
            text-align: center;
            font-size: 11pt;
            color: #4F46E5;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .seccion-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 15px;
        }
        .grid-2 {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .grid-2 td {
            width: 50%;
            vertical-align: top;
        }
        table.tabla-items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        table.tabla-items th, table.tabla-items td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            font-size: 10pt;
        }
        table.tabla-items th {
            background: #f1f5f9;
            color: #0f172a;
            text-align: left;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .clausulas {
            font-size: 9pt;
            text-align: justify;
            color: #334155;
            line-height: 1.5;
            margin-top: 15px;
            margin-bottom: 40px;
        }
        .firmas-table {
            width: 100%;
            margin-top: 50px;
            border-collapse: collapse;
        }
        .firmas-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 40px;
        }
        .linea-firma {
            border-top: 1px solid #0f172a;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 10pt;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- ENCABEZADO Y LOGO -->
    <div class="header">
        <table>
            <tr>
                <td>
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" class="logo" alt="Logo Empresa">
                    @else
                        <h2 style="margin: 0; color: #4F46E5;">{{ $negocio->nombre_comercial ?? 'EMPRESA' }}</h2>
                    @endif
                </td>
                <td class="empresa-info">
                    <span class="bold" style="font-size: 12pt; color: #0f172a;">{{ $negocio->razon_social ?? $negocio->nombre_comercial ?? 'Mi Negocio' }}</span><br>
                    @if(!empty($negocio->nit)) NIT: {{ $negocio->nit }}<br> @endif
                    @if(!empty($negocio->direccion)) {{ $negocio->direccion }}<br> @endif
                    @if(!empty($negocio->telefono)) Tel: {{ $negocio->telefono }} @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="titulo-doc">Contrato de Compraventa a Plazos</div>
    <div class="subtitulo-doc">Referencia: {{ $ventaCuota->numero_referencia }}</div>

    <!-- DATOS GENERALES -->
    <table class="grid-2">
        <tr>
            <td style="padding-right: 10px;">
                <div class="seccion-box">
                    <span class="bold" style="color: #4F46E5;">DATOS DEL CLIENTE</span><br>
                    <strong>Nombre:</strong> {{ $ventaCuota->cliente->nombre }}<br>
                    <strong>NIT / DPI:</strong> {{ $ventaCuota->cliente->numero_documento ?? 'CF' }}<br>
                    <strong>Teléfono:</strong> {{ $ventaCuota->cliente->telefono ?? 'N/D' }}<br>
                    <strong>Dirección:</strong> {{ $ventaCuota->cliente->direccion ?? 'Ciudad' }}
                </div>
            </td>
            <td style="padding-left: 10px;">
                <div class="seccion-box">
                    <span class="bold" style="color: #4F46E5;">CONDICIONES DEL CRÉDITO</span><br>
                    <strong>Fecha de Emisión:</strong> {{ $ventaCuota->fecha_inicio->format('d/m/Y') }}<br>
                    <strong>Sucursal:</strong> {{ $ventaCuota->sucursal->nombre ?? 'Central' }}<br>
                    <strong>Plazo:</strong> {{ $ventaCuota->numero_cuotas }} Cuotas Mensuales<br>
                    <strong>Asesor / Vendedor:</strong> {{ $ventaCuota->user->name ?? 'Ventas' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- DETALLE DE ARTÍCULOS -->
    <div class="bold" style="font-size: 11pt; margin-bottom: 5px; color: #0f172a;">1. Artículos Adquiridos</div>
    <table class="tabla-items">
        <thead>
            <tr>
                <th style="width: 10%; text-align: center;">Cant.</th>
                <th style="width: 60%;">Descripción del Producto / Artículo</th>
                <th style="width: 15%; text-align: right;">Precio Unit.</th>
                <th style="width: 15%; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventaCuota->detalles as $det)
            <tr>
                <td style="text-align: center;">{{ number_format($det->cantidad, 0) }}</td>
                <td>{{ $det->producto->nombre ?? 'Artículo' }}</td>
                <td style="text-align: right;">Q{{ number_format($det->precio_unitario, 2) }}</td>
                <td style="text-align: right;">Q{{ number_format($det->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- RESUMEN FINANCIERO -->
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%;">
                <table style="width: 100%; border-collapse: collapse; font-size: 10pt;">
                    <tr>
                        <td><strong>Precio Base:</strong></td>
                        <td class="text-right">Q{{ number_format($ventaCuota->precio_base, 2) }}</td>
                    </tr>
                    @if((float)$ventaCuota->descuento > 0)
                    <tr>
                        <td>Descuento:</td>
                        <td class="text-right">-Q{{ number_format($ventaCuota->descuento, 2) }}</td>
                    </tr>
                    @endif
                    @if((float)$ventaCuota->porcentaje_interes > 0)
                    <tr>
                        <td>Interés Financiero ({{ $ventaCuota->porcentaje_interes }}%):</td>
                        <td class="text-right">Q{{ number_format($ventaCuota->total - ($ventaCuota->precio_base - $ventaCuota->descuento), 2) }}</td>
                    </tr>
                    @endif
                    <tr style="border-top: 1px solid #cbd5e1;">
                        <td><strong>Total Contrato:</strong></td>
                        <td class="text-right bold">Q{{ number_format($ventaCuota->total, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Enganche Recibido:</td>
                        <td class="text-right bold" style="color: #16a34a;">-Q{{ number_format($ventaCuota->enganche, 2) }}</td>
                    </tr>
                    <tr style="border-top: 2px solid #0f172a; font-size: 11pt;">
                        <td style="padding-top: 4px;"><strong>Saldo Financiado:</strong></td>
                        <td class="text-right bold" style="padding-top: 4px; color: #4F46E5;">Q{{ number_format($ventaCuota->saldo_financiar, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- CRONOGRAMA DE PAGOS -->
    <div class="bold" style="font-size: 11pt; margin-bottom: 5px; color: #0f172a;">2. Cronograma de Pagos (Pagaré)</div>
    <table class="tabla-items">
        <thead>
            <tr>
                <th style="width: 20%; text-align: center;">No. Letra</th>
                <th style="width: 40%;">Fecha de Vencimiento Límite</th>
                <th style="width: 40%; text-align: right;">Monto de la Cuota</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventaCuota->cuotas as $cuota)
            <tr>
                <td style="text-align: center; font-weight: bold;">Cuota #{{ $cuota->numero_cuota }}</td>
                <td>{{ $cuota->fecha_vencimiento->format('d/m/Y') }}</td>
                <td style="text-align: right; font-weight: bold;">Q{{ number_format($cuota->monto_cuota, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- CLÁUSULAS LEGALES -->
    <div class="bold" style="font-size: 11pt; margin-bottom: 3px; color: #0f172a;">3. Cláusulas y Aceptación</div>
    <div class="clausulas">
        Por medio del presente documento, el comprador de manera voluntaria adquiere los bienes arriba detallados bajo la modalidad de crédito a plazos, comprometiéndose a pagar de forma puntual e incondicional cada una de las cuotas en las fechas de vencimiento establecidas en el cronograma anterior. En caso de retraso o mora, se aplicarán los recargos estipulados por la ley y las políticas comerciales de la empresa. El presente documento adquiere fuerza ejecutiva y carácter de título de crédito (Pagaré) en caso de incumplimiento de pago.
    </div>

    <!-- ESPACIO DE FIRMAS -->
    <table class="firmas-table">
        <tr>
            <td>
                <div class="linea-firma">
                    Firma del Comprador<br>
                    <span style="font-weight: normal; font-size: 9pt;">DPI / NIT: {{ $ventaCuota->cliente->numero_documento ?? 'CF' }}</span>
                </div>
            </td>
            <td>
                <div class="linea-firma">
                    Por la Empresa / Autorizado<br>
                    <span style="font-weight: normal; font-size: 9pt;">{{ $negocio->razon_social ?? $negocio->nombre_comercial ?? 'Empresa' }}</span>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>