<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Cobro - {{ $pago->id }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 10px;
            width: 72mm;
            box-sizing: border-box;
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .linea { border-bottom: 1px dashed #000; margin: 6px 0; }
        .tabla-datos { width: 100%; border-collapse: collapse; font-size: 11px; margin: 4px 0; }
        .tabla-datos td { padding: 2px 0; vertical-align: top; }
        .monto-destacado {
            font-size: 16px;
            font-weight: bold;
            padding: 4px 0;
            text-align: center;
            border: 1px solid #000;
            margin: 8px 0;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print text-center" style="margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 6px 12px; font-size: 12px; font-weight: bold; cursor: pointer;">
            Reimprimir Comprobante
        </button>
    </div>

    <div class="text-center">
        <div class="bold" style="font-size: 14px;">
            {{ $negocio->nombre_comercial ?? $negocio->nombre_negocio ?? 'MI NEGOCIO' }}
        </div>
        @if(!empty($negocio->razon_social) && $negocio->razon_social !== ($negocio->nombre_comercial ?? ''))
            <div style="font-size: 10px;">{{ $negocio->razon_social }}</div>
        @endif
        @if(!empty($negocio->nit))
            <div style="font-size: 10px;">NIT: {{ $negocio->nit }}</div>
        @endif
        @if(!empty($negocio->direccion))
            <div style="font-size: 10px;">{{ $negocio->direccion }}</div>
        @endif
        @if(!empty($negocio->telefono))
            <div style="font-size: 10px;">Tel: {{ $negocio->telefono }}</div>
        @endif

        <div style="margin-top: 4px; font-weight: bold;">COMPROBANTE DE PAGO / ABONO</div>
        <div style="font-size: 10px;">Sucursal: {{ $pago->ventaCuota->sucursal->nombre ?? 'Central' }}</div>
    </div>

    <div class="linea"></div>

    <table class="tabla-datos">
        <tr>
            <td class="bold" style="width: 45%;">Recibo No:</td>
            <td class="text-right">REC-{{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <td class="bold">Fecha / Hora:</td>
            <td class="text-right">{{ $pago->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="bold">Contrato Ref:</td>
            <td class="text-right">{{ $pago->ventaCuota->numero_referencia }}</td>
        </tr>
        <tr>
            <td class="bold">Cuota No:</td>
            <td class="text-right">Cuota #{{ $pago->cuota->numero_cuota }} de {{ $pago->ventaCuota->numero_cuotas }}</td>
        </tr>
        <tr>
            <td class="bold">Cobrador:</td>
            <td class="text-right">{{ $pago->user->name ?? 'Cajero' }}</td>
        </tr>
    </table>

    <div class="linea"></div>

    <div>
        <span class="bold">Cliente:</span> {{ $pago->cliente->nombre }}<br>
        <span class="bold">Doc / NIT:</span> {{ $pago->cliente->numero_documento ?? 'CF' }}
    </div>

    <div class="linea"></div>

    <div class="monto-destacado">
        ABONO: Q{{ number_format($pago->monto, 2) }}
    </div>

    <table class="tabla-datos">
        <tr>
            <td>Método de Pago:</td>
            <td class="text-right bold">{{ $pago->metodo_pago }}</td>
        </tr>
        @if($pago->referencia)
        <tr>
            <td>Referencia:</td>
            <td class="text-right">{{ $pago->referencia }}</td>
        </tr>
        @endif
        <tr>
            <td>Monto Total Cuota:</td>
            <td class="text-right">Q{{ number_format($pago->cuota->monto_cuota, 2) }}</td>
        </tr>
        <tr>
            <td>Saldo Pendiente Cuota:</td>
            <td class="text-right bold">Q{{ number_format($pago->cuota->saldo_pendiente, 2) }}</td>
        </tr>
        <tr>
            <td>Saldo Pendiente Contrato:</td>
            <td class="text-right bold">Q{{ number_format($pago->ventaCuota->saldo_pendiente, 2) }}</td>
        </tr>
    </table>

    @if($pago->notas)
        <div style="font-size: 10px; margin-top: 4px;">
            <span class="bold">Notas:</span> {{ $pago->notas }}
        </div>
    @endif

    <div class="linea"></div>

    <div class="text-center" style="font-size: 10px; margin-top: 15px;">
        <br><br>
        _______________________________<br>
        Firma y Sello de Cobro<br><br>
        ¡Gracias por su puntual abono!
    </div>

</body>
</html>