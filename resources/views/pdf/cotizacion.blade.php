<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización {{ $cotizacion->numero_referencia }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 13px; color: #111; margin: 0; padding: 20px; }
        .header-table, .info-table, .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-table td { vertical-align: top; }
        .logo { max-height: 85px; max-width: 250px; object-fit: contain; margin-bottom: 8px; display: block; }
        .company-name { font-size: 18px; font-weight: bold; color: #1f2937; margin-bottom: 3px; }
        .company-details { font-size: 12px; color: #4b5563; line-height: 1.4; }
        .table-header { 
            background-color: #1e40af !important; 
            color: #ffffff !important; 
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact; 
        }
        .table-header th { 
            color: #ffffff !important; 
            border: 1px solid #1e3a8a !important; 
            padding: 9px 10px; 
            font-size: 12px; 
            font-weight: bold; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
        }
        .items-table th, .items-table td { border: 1px solid #d1d5db; padding: 7px 10px; font-size: 12px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-section { width: 45%; float: right; margin-top: 10px; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 5px 8px; font-size: 12px; }
    </style>
</head>
<body onload="window.print()">

    <!-- Cabecera Dinámica: Logo + Empresa + Sucursal + Datos Fiscales -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo Empresa" class="logo">
                @endif

                <div class="company-name">{{ $negocio->nombre_comercial ?? $negocio->nombre_negocio ?? 'Mi Empresa' }}</div>
                <div class="company-details">
                    Razón Social: {{ $negocio->razon_social ?? '' }} | NIT: {{ $negocio->nit ?? 'CF' }}<br>
                    <strong>Sucursal: {{ $cotizacion->sucursal->nombre ?? 'Principal' }}</strong><br>
                    @if($cotizacion->sucursal && $cotizacion->sucursal->direccion)
                        Dirección: {{ $cotizacion->sucursal->direccion }}@if($cotizacion->sucursal->municipio), {{ $cotizacion->sucursal->municipio }}@endif<br>
                    @endif
                    Tel: {{ $cotizacion->sucursal->telefono ?? $negocio->telefono ?? 'N/A' }} | Email: {{ $negocio->email ?? 'N/A' }}
                </div>
            </td>
            <td class="text-right" style="width: 40%;">
                <h2 style="margin: 0; color: #1f2937; font-size: 22px;">COTIZACIÓN</h2>
                <div style="font-size: 15px; font-weight: bold; color: #374151; margin-top: 5px;">{{ $cotizacion->numero_referencia }}</div>
                <div style="font-size: 12px; color: #4b5563; margin-top: 3px;">
                    Fecha: {{ $cotizacion->fecha_emision->format('d/m/Y') }}<br>
                    Válido hasta: {{ $cotizacion->fecha_vencimiento->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <hr style="border: 0; border-top: 1px solid #e5e7eb; margin-bottom: 15px;">

    <!-- Datos del Cliente -->
    <table class="info-table" style="background: #f9fafb; padding: 10px; border-radius: 4px;">
        <tr>
            <td>
                <strong style="font-size: 12px; color: #374151; text-transform: uppercase;">Información del Cliente</strong><br>
                <span style="font-size: 14px; font-weight: bold;">{{ $cotizacion->cliente->nombre ?? 'Consumidor Final' }}</span><br>
                NIT / Doc: {{ $cotizacion->cliente->numero_documento ?? $cotizacion->cliente->nit ?? 'CF' }} &nbsp;|&nbsp; Teléfono: {{ $cotizacion->cliente->telefono ?? 'N/A' }}<br>
                Dirección: {{ $cotizacion->cliente->direccion ?? 'Ciudad' }}
            </td>
        </tr>
    </table>

    <!-- Tabla de Artículos con Cabecera Azul y Letras Blancas -->
    <table class="items-table">
        <thead>
            <tr class="table-header">
                <th class="text-center" style="width: 35px;">#</th>
                <th style="text-align: left;">Artículo / Descripción</th>
                <th class="text-center" style="width: 60px;">Cant.</th>
                <th class="text-right" style="width: 90px;">Precio U.</th>
                <th class="text-right" style="width: 90px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cotizacion->detalles as $index => $detalle)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $detalle->producto->nombre ?? 'Artículo' }}</strong>
                    @if($detalle->descripcion)
                        <br><small style="color: #6b7280;">{{ $detalle->descripcion }}</small>
                    @endif
                </td>
                <td class="text-center">{{ number_format($detalle->cantidad, 0) }}</td>
                <td class="text-right">Q{{ number_format($detalle->precio_unitario, 2) }}</td>
                <td class="text-right">Q{{ number_format($detalle->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Resumen de Totales -->
    <div style="clear: both;">
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td class="text-right">Q{{ number_format($cotizacion->subtotal_general, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Descuento:</strong></td>
                    <td class="text-right">{{ $cotizacion->descuento ? 'Q' . number_format($cotizacion->descuento, 2) : 'Q0.00' }}</td>
                </tr>
                <tr style="background: #f3f4f6; font-size: 14px; font-weight: bold;">
                    <td style="padding: 8px;">Gran Total:</td>
                    <td class="text-right" style="padding: 8px;">Q{{ number_format($cotizacion->costo_total, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if($cotizacion->observaciones)
    <div style="clear: both; padding-top: 30px; font-size: 12px; color: #4b5563;">
        <strong>Observaciones / Términos:</strong><br>
        {{ $cotizacion->observaciones }}
    </div>
    @endif

</body>
</html>