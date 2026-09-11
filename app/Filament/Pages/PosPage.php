<?php

namespace App\Filament\Pages;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ConfiguracionNegocio;
use App\Models\CuotaCobro;
use App\Models\DetalleVenta;
use App\Models\Lote;
use App\Models\PagoCuotaCobro;
use App\Models\PagoVenta;
use App\Models\Producto;
use App\Models\SesionCaja;
use App\Models\Venta;
use App\Models\VentaCuota;
use App\Services\FelplexService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PosPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationGroup = 'Ventas / POS';

    protected static ?string $navigationLabel = 'Punto de Venta (POS)';

    protected static ?string $title = 'Punto de Venta';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.pos-page';

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    // Sesión de caja
    public ?SesionCaja $sesionActiva = null;
    public ?int $sucursalId = null;

    // Catálogo y Búsqueda
    public string $search = '';
    public ?int $categoriaSeleccionadaId = null;

    // Cliente
    public ?int $clienteId = null;
    public ?array $clienteData = null;
    public string $busquedaCliente = '';
    public bool $mostrarBuscadorCliente = false;
    public array $listaClientesFiltrados = [];

    // Alta Rápida de Cliente
    public bool $showCrearClienteModal = false;
    public string $nuevoNombre = '';
    public string $nuevoTipoDoc = 'NIT';
    public string $nuevoNumeroDoc = 'CF';
    public string $nuevoTelefono = '';
    public string $nuevoDireccion = 'CIUDAD';

    // Carrito de compras
    public array $cart = [];
    public ?int $selectedCartIndex = null;
    public string $activeNumpadMode = 'cant';
    public string $numpadBuffer = '';

    // Parámetros Fiscales del Régimen SAT
    public string $codigoRegimen = 'PEQUENO_5';
    public float $porcentajeImpuesto = 0.05;
    public string $etiquetaImpuesto = 'Impuesto Pequeño Contribuyente (5%)';

    // Totales
    public float $subtotal = 0.0;
    public float $descuentoGeneral = 0.0;
    public float $impuestos = 0.0;
    public float $total = 0.0;

    // Modal de Pago POS
    public bool $showPaymentModal = false;
    public array $lineasPago = [];
    public int $pagoActivoIndex = 0;
    public string $pagoNumpadBuffer = '';
    public float $totalPagado = 0.0;
    public float $restante = 0.0;
    public float $cambio = 0.0;

    // Ticket Final Contado
    public ?Venta $ultimaVenta = null;
    public ?int $ultimaVentaId = null;
    public bool $showTicketModal = false;
    public bool $felHabilitadoGlobal = false;

    // -------------------------------------------------------------
    // GESTIÓN DE COBRO DE CUOTAS INTEGRADO EN POS
    // -------------------------------------------------------------
    public bool $showCuotasModal = false;
    public string $busquedaCuota = '';
    public array $contratosEncontrados = [];
    public ?int $contratoSeleccionadoId = null;
    public ?array $contratoActivo = null;

    // Formulario de Abono de Cuota individual
    public ?int $cuotaSeleccionadaId = null;
    public ?array $cuotaSeleccionada = null;
    public float $montoAbonoCuota = 0.0;
    public string $metodoPagoCuota = 'EFECTIVO';
    public string $referenciaPagoCuota = '';
    
    // Modal de Éxito e Impresión de Recibo de Cuota
    public bool $showReciboCuotaModal = false;
    public ?array $ultimoPagoInfo = null;
    public ?int $ultimoPagoCuotaId = null;

    // -------------------------------------------------------------
    // MODAL DE CIERRE Y ARQUEO INTEGRAL DE CAJA (4 MÉTODOS)
    // -------------------------------------------------------------
    public bool $showCerrarCajaModal = false;

    // Efectivo
    public float $cierreMontoApertura = 0.0;
    public float $cierreVentasEfectivo = 0.0;
    public float $cierreCuotasEfectivo = 0.0;
    public float $cierreEfectivoEsperado = 0.0;
    public float $cierreEfectivoReal = 0.0;
    public float $cierreDiferenciaEfectivo = 0.0;

    // Tarjeta (Vouchers)
    public float $cierreTarjetaEsperado = 0.0;
    public float $cierreTarjetaReal = 0.0;
    public float $cierreDiferenciaTarjeta = 0.0;

    // Transferencia (Boletas)
    public float $cierreTransfEsperado = 0.0;
    public float $cierreTransfReal = 0.0;
    public float $cierreDiferenciaTransf = 0.0;

    // Cheques
    public float $cierreChequeEsperado = 0.0;
    public float $cierreChequeReal = 0.0;
    public float $cierreDiferenciaCheque = 0.0;

    // Totales Generales
    public float $cierreGranTotalEsperado = 0.0;
    public float $cierreGranTotalReal = 0.0;
    public float $cierreGranDiferencia = 0.0;
    public string $cierreObservaciones = '';

    public function mount(): void
    {
        $this->sucursalId = session('sucursal_activa_id');

        $this->sesionActiva = SesionCaja::where('user_id', auth()->id())
            ->where('estado', 'ABIERTA')
            ->whereHas('caja', fn ($q) => $q->where('sucursal_id', $this->sucursalId))
            ->latest('fecha_apertura')
            ->first();

        $config = ConfiguracionNegocio::first();
        $this->felHabilitadoGlobal = $config ? (bool) $config->fel_habilitado : false;

        $this->determinarRegimenFiscal();
        $this->inicializarCliente();
        $this->actualizarListaClientes();
    }

    public function determinarRegimenFiscal(): void
    {
        $regimen = null;

        if (Schema::hasTable('configuracion_negocio')) {
            if (Schema::hasColumn('configuracion_negocio', 'regimen_impuestos')) {
                $regimen = DB::table('configuracion_negocio')->value('regimen_impuestos');
            } elseif (Schema::hasColumn('configuracion_negocio', 'regimen_tributario')) {
                $regimen = DB::table('configuracion_negocio')->value('regimen_tributario');
            }
        }

        $this->codigoRegimen = strtoupper((string) ($regimen ?? 'PEQUENO_5'));

        if (str_contains($this->codigoRegimen, 'PEQUENO_5') || str_contains($this->codigoRegimen, 'PEQUEÑO') || $this->codigoRegimen === '5') {
            $this->porcentajeImpuesto = 0.05;
            $this->etiquetaImpuesto = 'Impuesto Pequeño Contribuyente (5%)';
        } elseif (str_contains($this->codigoRegimen, '4') || str_contains($this->codigoRegimen, 'ELECTR')) {
            $this->porcentajeImpuesto = 0.04;
            $this->etiquetaImpuesto = 'Impuesto P.C. Electrónico (4%)';
        } elseif (str_contains($this->codigoRegimen, 'EXENTO') || $this->codigoRegimen === '0') {
            $this->porcentajeImpuesto = 0.0;
            $this->etiquetaImpuesto = 'Exento de Impuestos (0%)';
        } else {
            $this->porcentajeImpuesto = 0.12;
            $this->etiquetaImpuesto = 'Impuestos (IVA 12% incluido)';
        }
    }

    public function inicializarCliente(): void
    {
        $clienteDefault = Cliente::where('numero_documento', 'CF')
            ->orWhere('numero_documento', 'cf')
            ->first();

        if (! $clienteDefault) {
            $clienteDefault = Cliente::first();
        }

        if ($clienteDefault) {
            $this->asignarCliente($clienteDefault);
        }
    }

    public function asignarCliente(Cliente $cli): void
    {
        $this->clienteId = $cli->id;
        $doc = filled($cli->numero_documento) ? (string) $cli->numero_documento : 'CF';

        $this->clienteData = [
            'id' => $cli->id,
            'nombre' => $cli->nombre,
            'nit' => $doc,
            'tipo_doc' => $cli->tipo_documento ?? 'NIT',
            'credito_activo' => (bool) ($cli->credito_activo ?? false),
            'limite_credito' => (float) ($cli->limite_credito ?? 0),
            'saldo_deudor' => (float) ($cli->saldo_deudor ?? 0),
        ];

        $this->mostrarBuscadorCliente = false;
        $this->busquedaCliente = '';
    }

    public function updatedBusquedaCliente(): void
    {
        $this->actualizarListaClientes();
    }

    public function toggleBuscadorCliente(): void
    {
        $this->mostrarBuscadorCliente = ! $this->mostrarBuscadorCliente;
        if ($this->mostrarBuscadorCliente) {
            $this->actualizarListaClientes();
        }
    }

    public function actualizarListaClientes(): void
    {
        $query = Cliente::where('activo', true);

        if (! empty(trim($this->busquedaCliente))) {
            $term = trim($this->busquedaCliente);
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                  ->orWhere('numero_documento', 'like', "%{$term}%");
            });
        }

        $this->listaClientesFiltrados = $query->limit(8)->get()->map(function (Cliente $c) {
            return [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'nit' => filled($c->numero_documento) ? (string) $c->numero_documento : 'CF',
            ];
        })->toArray();
    }

    public function seleccionarCliente(int $id): void
    {
        $cli = Cliente::find($id);
        if ($cli) {
            $this->asignarCliente($cli);
        }
    }

    public function consultarNitEnFelplex(): void
    {
        if (empty(trim($this->nuevoNumeroDoc)) || strtoupper(trim($this->nuevoNumeroDoc)) === 'CF') {
            Notification::make()->title('Aviso')->body('Ingresa un NIT o CUI válido para consultar.')->warning()->send();
            return;
        }

        $felService = new FelplexService();
        $resultado = $felService->buscarNitOCui($this->nuevoNumeroDoc);

        if ($resultado['success']) {
            $this->nuevoNombre = $resultado['nombre'];
            
            if (isset($resultado['tipo']) && $resultado['tipo'] === 'CUI') {
                $this->nuevoTipoDoc = 'CUI';
            } else {
                $this->nuevoTipoDoc = 'NIT';
            }

            if (!empty($resultado['direccion'])) {
                $this->nuevoDireccion = $resultado['direccion'];
            }

            Notification::make()
                ->title("{$resultado['tipo']} Encontrado en la SAT")
                ->body("Contribuyente: {$resultado['nombre']}")
                ->success()
                ->send();
        } elseif (isset($resultado['no_registrado']) && $resultado['no_registrado'] === true) {
            if (isset($resultado['tipo']) && $resultado['tipo'] === 'CUI') {
                $this->nuevoTipoDoc = 'CUI';
            } else {
                $this->nuevoTipoDoc = 'NIT';
            }

            Notification::make()
                ->title('Documento Válido')
                ->body('No se encontró en caché de FELplex, puedes ingresar el nombre y dirección manualmente.')
                ->info()
                ->send();
        } else {
            Notification::make()
                ->title('Sin resultados')
                ->body($resultado['error'])
                ->warning()
                ->send();
        }
    }

    public function guardarClienteRapido(): void
    {
        $this->validate([
            'nuevoNombre' => 'required|min:3',
            'nuevoNumeroDoc' => 'required',
        ]);

        $datos = [
            'tipo_documento' => $this->nuevoTipoDoc,
            'numero_documento' => $this->nuevoNumeroDoc,
            'nombre' => $this->nuevoNombre,
            'direccion' => $this->nuevoDireccion,
            'activo' => true,
        ];

        if (Schema::hasColumn('clientes', 'telefono')) {
            $datos['telefono'] = $this->nuevoTelefono;
        }

        $cliente = Cliente::create($datos);
        $this->asignarCliente($cliente);
        $this->actualizarListaClientes();

        $this->showCrearClienteModal = false;
        $this->nuevoNombre = '';
        $this->nuevoNumeroDoc = 'CF';
        $this->nuevoTelefono = '';

        Notification::make()->title('Cliente Registrado')->success()->send();
    }

    public function seleccionarCategoria(?int $catId): void
    {
        $this->categoriaSeleccionadaId = $catId;
    }

    public function agregarAlCarrito(int $productoId): void
    {
        if (! $this->sesionActiva) {
            Notification::make()->title('Turno Cerrado')->body('Abre un turno de caja para vender.')->danger()->send();
            return;
        }

        $producto = Producto::find($productoId);
        if (! $producto) return;

        $stockActual = $producto->stockEnSucursal($this->sucursalId);

        if ($producto->tipo === 'BIEN' && $stockActual <= 0) {
            Notification::make()->title('Sin Existencias')->body("No hay existencias de {$producto->nombre}.")->warning()->send();
            return;
        }

        $encontrado = false;
        foreach ($this->cart as $idx => $item) {
            if ($item['id'] === $productoId) {
                if ($producto->tipo === 'BIEN' && ($item['cantidad'] + 1) > $stockActual) {
                    Notification::make()->title('Límite de Stock')->body("Solo hay {$stockActual} unidades disponibles.")->warning()->send();
                    return;
                }
                $this->cart[$idx]['cantidad']++;
                $this->cart[$idx]['subtotal'] = round($this->cart[$idx]['cantidad'] * $this->cart[$idx]['precio'], 2);
                $this->selectedCartIndex = $idx;
                $encontrado = true;
                break;
            }
        }

        if (! $encontrado) {
            $this->cart[] = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo' => $producto->codigo_interno,
                'precio' => (float) $producto->precio_venta,
                'precio_original' => (float) $producto->precio_venta,
                'cantidad' => 1,
                'descuento_porcentaje' => 0,
                'subtotal' => (float) $producto->precio_venta,
                'stock_disponible' => $stockActual,
                'tipo' => $producto->tipo,
                'maneja_lotes' => (bool) $producto->maneja_lotes,
            ];
            $this->selectedCartIndex = count($this->cart) - 1;
        }

        $this->numpadBuffer = '';
        $this->recalcularTotales();
    }

    public function selectCartItem(int $index): void
    {
        $this->selectedCartIndex = $index;
        $this->numpadBuffer = '';
    }

    public function setNumpadMode(string $mode): void
    {
        $this->activeNumpadMode = $mode;
        $this->numpadBuffer = '';
    }

    public function pressNumpad(string $key): void
    {
        if ($this->selectedCartIndex === null || ! isset($this->cart[$this->selectedCartIndex])) {
            return;
        }

        $idx = $this->selectedCartIndex;

        if ($key === 'backspace') {
            if ($this->numpadBuffer === '' || (float) $this->cart[$idx]['cantidad'] <= 0) {
                $this->eliminarDelCarrito($idx);
                return;
            }

            $this->numpadBuffer = substr($this->numpadBuffer, 0, -1);

            if ($this->numpadBuffer === '' || $this->numpadBuffer === '-') {
                if ($this->activeNumpadMode === 'cant') {
                    $this->cart[$idx]['cantidad'] = 0;
                    $this->cart[$idx]['subtotal'] = 0;
                    $this->recalcularTotales();
                    return;
                }
            }
        } elseif ($key === '+/-') {
            if (str_starts_with($this->numpadBuffer, '-')) {
                $this->numpadBuffer = substr($this->numpadBuffer, 1);
            } else {
                $this->numpadBuffer = '-' . $this->numpadBuffer;
            }
        } else {
            if ($key === '.' && str_contains($this->numpadBuffer, '.')) {
                return;
            }
            $this->numpadBuffer .= $key;
        }

        $valor = (float) ($this->numpadBuffer === '' ? 0 : $this->numpadBuffer);

        if ($this->activeNumpadMode === 'cant') {
            $item = $this->cart[$idx];
            if ($item['tipo'] === 'BIEN' && $valor > $item['stock_disponible']) {
                Notification::make()->title('Stock Insuficiente')->body("Solo quedan {$item['stock_disponible']} unidades.")->warning()->send();
                $valor = $item['stock_disponible'];
                $this->numpadBuffer = (string) $valor;
            }
            $this->cart[$idx]['cantidad'] = $valor;
        } elseif ($this->activeNumpadMode === 'desc') {
            $this->cart[$idx]['descuento_porcentaje'] = min(100, max(0, $valor));
        } elseif ($this->activeNumpadMode === 'precio') {
            $this->cart[$idx]['precio'] = max(0, $valor);
        }

        $precioNeto = $this->cart[$idx]['precio'] * (1 - ($this->cart[$idx]['descuento_porcentaje'] / 100));
        $this->cart[$idx]['subtotal'] = round($this->cart[$idx]['cantidad'] * $precioNeto, 2);

        $this->recalcularTotales();
    }

    public function eliminarDelCarrito(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->selectedCartIndex = count($this->cart) > 0 ? (count($this->cart) - 1) : null;
        $this->numpadBuffer = '';
        $this->recalcularTotales();
    }

    public function limpiarCarrito(): void
    {
        $this->cart = [];
        $this->selectedCartIndex = null;
        $this->numpadBuffer = '';
        $this->descuentoGeneral = 0.0;
        $this->recalcularTotales();
    }

    public function procesarCodigoBarras(): void
    {
        if (empty(trim($this->search))) return;

        $codigo = trim($this->search);
        $producto = Producto::where(function ($q) use ($codigo) {
            $q->where('codigo_barras', $codigo)
              ->orWhere('codigo_interno', $codigo);
        })->where('activo', true)->first();

        if ($producto) {
            $this->agregarAlCarrito($producto->id);
            $this->search = '';
        }
    }

    public function recalcularTotales(): void
    {
        $sub = 0;
        foreach ($this->cart as $item) {
            $sub += (float) $item['subtotal'];
        }

        $this->subtotal = round($sub, 2);
        $totalFinal = max(0, round($this->subtotal - (float) $this->descuentoGeneral, 2));

        if ($this->porcentajeImpuesto === 0.05) {
            $this->impuestos = round($totalFinal * 0.05, 2);
        } elseif ($this->porcentajeImpuesto === 0.04) {
            $this->impuestos = round($totalFinal * 0.04, 2);
        } elseif ($this->porcentajeImpuesto === 0.12) {
            $baseGravable = $totalFinal / 1.12;
            $this->impuestos = round($totalFinal - $baseGravable, 2);
        } else {
            $this->impuestos = 0.0;
        }

        $this->total = $totalFinal;
    }

    public function abrirModalPago(): void
    {
        if (empty($this->cart) || $this->total <= 0) {
            Notification::make()->title('Carrito Vacío')->warning()->send();
            return;
        }

        $this->lineasPago = [
            [
                'metodo' => 'EFECTIVO',
                'monto' => $this->total,
                'referencia' => '',
            ],
        ];

        $this->pagoActivoIndex = 0;
        $this->pagoNumpadBuffer = (string) $this->total;
        $this->recalcularBalancePago();
        $this->showPaymentModal = true;
    }

    public function seleccionarLineaPago(int $idx): void
    {
        $this->pagoActivoIndex = $idx;
        $this->pagoNumpadBuffer = (string) ($this->lineasPago[$idx]['monto'] ?? 0);
    }

    public function actualizarMontoLineaPago(int $idx, $monto): void
    {
        $montoFloat = max(0, (float) $monto);
        $this->lineasPago[$idx]['monto'] = $montoFloat;
        $this->pagoActivoIndex = $idx;
        $this->pagoNumpadBuffer = (string) $montoFloat;
        $this->recalcularBalancePago();
    }

    public function agregarMetodoPago(string $metodo): void
    {
        if ($metodo === 'CREDITO') {
            Notification::make()
                ->title('Método no permitido')
                ->body('El punto de venta opera exclusivamente con pagos de contado.')
                ->warning()
                ->send();
            return;
        }

        $restanteActual = max(0, $this->restante);

        if (isset($this->lineasPago[$this->pagoActivoIndex]) && $this->lineasPago[$this->pagoActivoIndex]['monto'] == 0) {
            $this->lineasPago[$this->pagoActivoIndex]['metodo'] = $metodo;
            return;
        }

        $this->lineasPago[] = [
            'metodo' => $metodo,
            'monto' => $restanteActual,
            'referencia' => '',
        ];

        $this->pagoActivoIndex = count($this->lineasPago) - 1;
        $this->pagoNumpadBuffer = (string) $restanteActual;
        $this->recalcularBalancePago();
    }

    public function eliminarLineaPago(int $idx): void
    {
        unset($this->lineasPago[$idx]);
        $this->lineasPago = array_values($this->lineasPago);

        if (empty($this->lineasPago)) {
            $this->lineasPago[] = [
                'metodo' => 'EFECTIVO',
                'monto' => $this->total,
                'referencia' => '',
            ];
            $this->pagoActivoIndex = 0;
        } else {
            $this->pagoActivoIndex = max(0, count($this->lineasPago) - 1);
        }

        $this->pagoNumpadBuffer = (string) $this->lineasPago[$this->pagoActivoIndex]['monto'];
        $this->recalcularBalancePago();
    }

    public function pressPagoNumpad(string $key): void
    {
        if (! isset($this->lineasPago[$this->pagoActivoIndex])) return;

        if ($key === 'backspace') {
            $this->pagoNumpadBuffer = substr($this->pagoNumpadBuffer, 0, -1);
        } elseif ($key === '+/-') {
            if (str_starts_with($this->pagoNumpadBuffer, '-')) {
                $this->pagoNumpadBuffer = substr($this->pagoNumpadBuffer, 1);
            } else {
                $this->pagoNumpadBuffer = '-' . $this->pagoNumpadBuffer;
            }
        } else {
            if ($key === '.' && str_contains($this->pagoNumpadBuffer, '.')) return;
            $this->pagoNumpadBuffer .= $key;
        }

        $valor = (float) ($this->pagoNumpadBuffer === '' ? 0 : $this->pagoNumpadBuffer);
        $this->lineasPago[$this->pagoActivoIndex]['monto'] = max(0, $valor);
        $this->recalcularBalancePago();
    }

    public function sumarBilleteRapido(int $cantidad): void
    {
        if (! isset($this->lineasPago[$this->pagoActivoIndex])) return;

        $actual = (float) $this->lineasPago[$this->pagoActivoIndex]['monto'];
        $nuevo = $actual + $cantidad;
        $this->lineasPago[$this->pagoActivoIndex]['monto'] = $nuevo;
        $this->pagoNumpadBuffer = (string) $nuevo;
        $this->recalcularBalancePago();
    }

    public function recalcularBalancePago(): void
    {
        $sum = 0;
        foreach ($this->lineasPago as $linea) {
            $sum += (float) ($linea['monto'] ?? 0);
        }

        $this->totalPagado = round($sum, 2);

        if ($this->totalPagado >= $this->total) {
            $this->restante = 0.0;
            $this->cambio = round($this->totalPagado - $this->total, 2);
        } else {
            $this->restante = round($this->total - $this->totalPagado, 2);
            $this->cambio = 0.0;
        }
    }

    public function procesarVenta(): void
    {
        if (! $this->sesionActiva || empty($this->cart)) return;

        $this->recalcularBalancePago();

        if ($this->totalPagado < $this->total) {
            Notification::make()
                ->title('Pago Incompleto')
                ->body('En el punto de venta solo se realizan ventas de contado. Saldo faltante: Q' . number_format($this->restante, 2))
                ->danger()
                ->send();
            return;
        }

        $venta = DB::transaction(function () {
            $ticketNum = 'POS-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $montoNetoCobrado = (float) $this->total;

            $nuevaVenta = Venta::create([
                'sesion_caja_id' => $this->sesionActiva->id,
                'sucursal_id' => $this->sucursalId,
                'cliente_id' => $this->clienteId,
                'user_id' => auth()->id(),
                'numero_ticket' => $ticketNum,
                'fecha_venta' => now(),
                'fecha_vencimiento' => null,
                'tipo_venta' => 'CONTADO',
                'estado' => 'COMPLETADA',
                'estado_pago' => 'PAGADO',
                'subtotal' => $this->subtotal,
                'descuento' => $this->descuentoGeneral,
                'impuesto' => $this->impuestos,
                'total' => $this->total,
                'monto_pagado' => $montoNetoCobrado,
                'saldo_pendiente' => 0.00,
                'cambio_entregado' => $this->cambio,
                'tipo_dte' => 'FACT',
            ]);

            foreach ($this->cart as $item) {
                $producto = Producto::lockForUpdate()->find($item['id']);
                $cantidadRestante = (float) $item['cantidad'];

                if ($producto->tipo === 'BIEN') {
                    $pivot = $producto->sucursales()->where('sucursal_id', $this->sucursalId)->first();
                    if ($pivot) {
                        $nuevoStock = max(0, (float) $pivot->pivot->stock_actual - $cantidadRestante);
                        $producto->sucursales()->updateExistingPivot($this->sucursalId, [
                            'stock_actual' => $nuevoStock,
                        ]);
                    }

                    if ($producto->maneja_lotes) {
                        $lotes = Lote::where('producto_id', $producto->id)
                            ->where('sucursal_id', $this->sucursalId)
                            ->where('activo', true)
                            ->where('cantidad_actual', '>', 0)
                            ->orderBy('fecha_vencimiento', 'asc')
                            ->orderBy('id', 'asc')
                            ->lockForUpdate()
                            ->get();

                        foreach ($lotes as $lote) {
                            if ($cantidadRestante <= 0) break;

                            $consumo = min($cantidadRestante, (float) $lote->cantidad_actual);
                            $lote->update([
                                'cantidad_actual' => (float) $lote->cantidad_actual - $consumo,
                                'activo' => ((float) $lote->cantidad_actual - $consumo) > 0,
                            ]);

                            DetalleVenta::create([
                                'venta_id' => $nuevaVenta->id,
                                'producto_id' => $producto->id,
                                'lote_id' => $lote->id,
                                'cantidad' => $consumo,
                                'precio_unitario' => $item['precio'],
                                'costo_unitario_historico' => $lote->costo_unitario,
                                'descuento' => $item['descuento_porcentaje'],
                                'subtotal' => round($consumo * ($item['precio'] * (1 - ($item['descuento_porcentaje'] / 100))), 2),
                            ]);

                            $cantidadRestante -= $consumo;
                        }
                    } else {
                        DetalleVenta::create([
                            'venta_id' => $nuevaVenta->id,
                            'producto_id' => $producto->id,
                            'cantidad' => $item['cantidad'],
                            'precio_unitario' => $item['precio'],
                            'costo_unitario_historico' => $producto->precio_compra,
                            'descuento' => $item['descuento_porcentaje'],
                            'subtotal' => $item['subtotal'],
                        ]);
                    }
                } else {
                    DetalleVenta::create([
                        'venta_id' => $nuevaVenta->id,
                        'producto_id' => $producto->id,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['precio'],
                        'costo_unitario_historico' => $producto->precio_compra,
                        'descuento' => $item['descuento_porcentaje'],
                        'subtotal' => $item['subtotal'],
                    ]);
                }
            }

            $cambioRestante = $this->cambio;
            foreach ($this->lineasPago as $pago) {
                $montoEntregado = (float) $pago['monto'];
                if ($montoEntregado <= 0) continue;

                $cambioLinea = 0.0;
                if ($pago['metodo'] === 'EFECTIVO' && $cambioRestante > 0) {
                    $cambioLinea = min($cambioRestante, $montoEntregado);
                    $cambioRestante -= $cambioLinea;
                }

                $montoNeto = round($montoEntregado - $cambioLinea, 2);

                PagoVenta::create([
                    'venta_id' => $nuevaVenta->id,
                    'sesion_caja_id' => $this->sesionActiva->id,
                    'user_id' => auth()->id(),
                    'metodo_pago' => $pago['metodo'],
                    'monto' => $montoNeto,
                    'recibido' => $montoEntregado,
                    'cambio' => $cambioLinea,
                    'referencia_pago' => $pago['referencia'] ?? null,
                ]);
            }

            return $nuevaVenta;
        });

        // La venta se procesa localmente de forma limpia sin certificar automáticamente,
        // permitiendo que el usuario decida si imprime ticket o certifica desde el modal de éxito.

        $this->ultimaVenta = $venta;
        $this->ultimaVentaId = $venta->id;

        $this->showPaymentModal = false;
        $this->limpiarCarrito();
        $this->showTicketModal = true;

        Notification::make()->title('Venta Finalizada Exitosamente')->success()->send();
    }

    public function certificarVentaActual(): void
    {
        if (!$this->ultimaVenta || filled($this->ultimaVenta->fel_uuid)) return;

        $felService = new FelplexService();
        $resultadoFel = $felService->certificarVentaPos($this->ultimaVenta);

        if ($resultadoFel['success']) {
            $this->ultimaVenta->update([
                'fel_uuid' => $resultadoFel['uuid'],
                'fel_serie' => $resultadoFel['serie'],
                'fel_numero' => $resultadoFel['numero'],
            ]);

            // Refrescar la instancia
            $this->ultimaVenta->refresh();

            Notification::make()
                ->title('¡Factura Electrónica Certificada!')
                ->body("DTE autorizado con éxito. UUID: {$resultadoFel['uuid']}")
                ->success()
                ->send();
        } else {
            $errorMsg = is_array($resultadoFel['error']) ? json_encode($resultadoFel['error']) : $resultadoFel['error'];
            
            Notification::make()
                ->title('Error al Certificar en FELplex')
                ->body($errorMsg)
                ->danger()
                ->send();
        }
    }

    // -------------------------------------------------------------
    // FUNCIONES DEL MODAL DE COBRO DE CUOTAS
    // -------------------------------------------------------------
    public function abrirModalCuotas(): void
    {
        if (! $this->sesionActiva) {
            Notification::make()
                ->title('Turno Cerrado')
                ->body('Debes abrir un turno de caja para registrar cobros.')
                ->danger()
                ->send();
            return;
        }

        $this->busquedaCuota = '';
        $this->cuotaSeleccionadaId = null;
        $this->cuotaSeleccionada = null;
        $this->buscarContratosCuotas();
        $this->showCuotasModal = true;
    }

    public function updatedBusquedaCuota(): void
    {
        $this->buscarContratosCuotas();
    }

    public function buscarContratosCuotas(): void
    {
        $query = VentaCuota::with(['cliente', 'cuotas'])
            ->where('estado', '!=', 'ANULADO')
            ->where('saldo_pendiente', '>', 0);

        if (! empty(trim($this->busquedaCuota))) {
            $termino = trim($this->busquedaCuota);
            $query->where(function ($q) use ($termino) {
                $q->where('numero_referencia', 'like', "%{$termino}%")
                  ->orWhereHas('cliente', function ($sq) use ($termino) {
                      $sq->where('nombre', 'like', "%{$termino}%")
                         ->orWhere('numero_documento', 'like', "%{$termino}%");
                  });
            });
        }

        $this->contratosEncontrados = $query->latest('id')->limit(8)->get()->map(function ($vc) {
            return [
                'id' => $vc->id,
                'referencia' => $vc->numero_referencia,
                'cliente' => $vc->cliente->nombre ?? 'N/A',
                'doc' => $vc->cliente->numero_documento ?? 'CF',
                'total' => (float) $vc->total,
                'saldo' => (float) $vc->saldo_pendiente,
                'cuotas_pendientes' => $vc->cuotas->where('estado', '!=', 'PAGADO')->count(),
            ];
        })->toArray();

        if ($this->contratoSeleccionadoId) {
            $this->cargarContrato($this->contratoSeleccionadoId);
        } elseif (! empty($this->contratosEncontrados)) {
            $this->cargarContrato($this->contratosEncontrados[0]['id']);
        } else {
            $this->contratoActivo = null;
        }
    }

    public function seleccionarContrato(int $id): void
    {
        $this->contratoSeleccionadoId = $id;
        $this->cargarContrato($id);
    }

    public function cargarContrato(int $id): void
    {
        $vc = VentaCuota::with(['cliente', 'cuotas' => fn ($q) => $q->orderBy('numero_cuota', 'asc')])->find($id);

        if (! $vc) {
            $this->contratoActivo = null;
            return;
        }

        $this->contratoActivo = [
            'id' => $vc->id,
            'referencia' => $vc->numero_referencia,
            'cliente' => $vc->cliente->nombre,
            'telefono' => $vc->cliente->telefono ?? '-',
            'total' => (float) $vc->total,
            'saldo_pendiente' => (float) $vc->saldo_pendiente,
            'cuotas' => $vc->cuotas->map(fn ($c) => [
                'id' => $c->id,
                'numero' => $c->numero_cuota,
                'monto' => (float) $c->monto_cuota,
                'saldo' => (float) $c->saldo_pendiente,
                'pagado' => (float) $c->monto_pagado,
                'vencimiento' => $c->fecha_vencimiento->format('d/m/Y'),
                'estado' => $c->estado,
                'vencida' => $c->fecha_vencimiento->isPast() && $c->estado !== 'PAGADO',
            ])->toArray(),
        ];

        $this->cuotaSeleccionadaId = null;
        $this->cuotaSeleccionada = null;
    }

    public function prepararCobroCuota(int $cuotaId): void
    {
        $cuota = CuotaCobro::with('ventaCuota.cliente')->find($cuotaId);
        if (! $cuota) return;

        $this->cuotaSeleccionadaId = $cuota->id;
        $this->cuotaSeleccionada = [
            'id' => $cuota->id,
            'numero' => $cuota->numero_cuota,
            'contrato' => $cuota->ventaCuota->numero_referencia,
            'cliente' => $cuota->ventaCuota->cliente->nombre ?? 'N/A',
            'monto_original' => (float) $cuota->monto_cuota,
            'saldo_pendiente' => (float) $cuota->saldo_pendiente,
        ];

        $this->montoAbonoCuota = (float) $cuota->saldo_pendiente;
        $this->metodoPagoCuota = 'EFECTIVO';
        $this->referenciaPagoCuota = '';
    }

    public function procesarAbonoCuota(): void
    {
        if (! $this->sesionActiva) {
            Notification::make()->title('Turno Requerido')->body('No puedes cobrar sin un turno de caja abierto.')->danger()->send();
            return;
        }

        if (! $this->cuotaSeleccionadaId || $this->montoAbonoCuota <= 0) {
            Notification::make()->title('Monto Inválido')->body('Ingresa un monto mayor a 0 para abonar.')->warning()->send();
            return;
        }

        $cuota = CuotaCobro::with('ventaCuota.cliente')->find($this->cuotaSeleccionadaId);
        if (! $cuota || $cuota->estado === 'PAGADO') {
            Notification::make()->title('Cuota ya cancelada')->warning()->send();
            return;
        }

        if ($this->montoAbonoCuota > (float) $cuota->saldo_pendiente) {
            Notification::make()->title('Monto Excede la Cuota')->body('No puedes abonar más del saldo pendiente de esta letra.')->warning()->send();
            return;
        }

        $pagoId = null;
        $pagoDataModal = [];

        DB::transaction(function () use ($cuota, &$pagoId, &$pagoDataModal) {
            $montoAbonar = round((float) $this->montoAbonoCuota, 2);
            $clienteIdCobro = $cuota->cliente_id ?? $cuota->ventaCuota?->cliente_id;

            $pago = PagoCuotaCobro::create([
                'cuota_cobro_id' => $cuota->id,
                'venta_cuota_id' => $cuota->venta_cuota_id,
                'cliente_id' => $clienteIdCobro,
                'sesion_caja_id' => $this->sesionActiva->id,
                'user_id' => auth()->id(),
                'metodo_pago' => $this->metodoPagoCuota,
                'monto' => $montoAbonar,
                'referencia_pago' => filled($this->referenciaPagoCuota) ? $this->referenciaPagoCuota : null,
                'fecha_pago' => now(),
            ]);

            $pagoId = $pago->id;

            $nuevoPagadoCuota = round((float) $cuota->monto_pagado + $montoAbonar, 2);
            $nuevoSaldoCuota = max(0, round((float) $cuota->saldo_pendiente - $montoAbonar, 2));
            $nuevoEstadoCuota = $nuevoSaldoCuota <= 0 ? 'PAGADO' : 'PARCIAL';

            $cuota->update([
                'monto_pagado' => $nuevoPagadoCuota,
                'saldo_pendiente' => $nuevoSaldoCuota,
                'estado' => $nuevoEstadoCuota,
            ]);

            $ventaCuota = $cuota->ventaCuota;
            $nuevoSaldoContrato = 0;
            if ($ventaCuota) {
                $nuevoSaldoContrato = max(0, round((float) $ventaCuota->saldo_pendiente - $montoAbonar, 2));
                $nuevoEstadoContrato = $nuevoSaldoContrato <= 0 ? 'LIQUIDADO' : 'ACTIVO';

                $ventaCuota->update([
                    'saldo_pendiente' => $nuevoSaldoContrato,
                    'estado' => $nuevoEstadoContrato,
                ]);

                if (Schema::hasColumn('clientes', 'saldo_deudor')) {
                    $cli = $ventaCuota->cliente;
                    if ($cli) {
                        $nuevoSaldoCliente = max(0, round((float) $cli->saldo_deudor - $montoAbonar, 2));
                        $cli->update(['saldo_deudor' => $nuevoSaldoCliente]);
                    }
                }
            }

            $pagoDataModal = [
                'id' => $pago->id,
                'contrato' => $ventaCuota->numero_referencia ?? 'INS',
                'cliente' => $cuota->cliente->nombre ?? $ventaCuota->cliente->nombre ?? 'Cliente',
                'numero_cuota' => $cuota->numero_cuota,
                'monto_abonado' => $montoAbonar,
                'saldo_cuota' => $nuevoSaldoCuota,
                'saldo_contrato' => $nuevoSaldoContrato,
                'metodo_pago' => $this->metodoPagoCuota,
            ];
        });

        $this->ultimoPagoCuotaId = $pagoId;
        $this->ultimoPagoInfo = $pagoDataModal;
        $this->cuotaSeleccionadaId = null;
        $this->cuotaSeleccionada = null;

        $this->showReciboCuotaModal = true;
        $this->buscarContratosCuotas();
    }

    // -------------------------------------------------------------
    // FUNCIONES DEL MODAL DE CIERRE Y ARQUEO INTEGRAL (4 MÉTODOS)
    // -------------------------------------------------------------
    public function abrirModalCierreCaja(): void
    {
        if (! $this->sesionActiva) return;

        // Desglose de Ventas POS en esta sesión
        $ventasDesglose = PagoVenta::whereHas('venta', function ($q) {
            $q->where('sesion_caja_id', $this->sesionActiva->id)
              ->where('estado', '!=', 'ANULADA');
        })
        ->selectRaw('metodo_pago, SUM(monto) as total')
        ->groupBy('metodo_pago')
        ->pluck('total', 'metodo_pago')
        ->toArray();

        // Desglose de Cuotas Cobradas en esta sesión
        $cuotasDesglose = PagoCuotaCobro::where('sesion_caja_id', $this->sesionActiva->id)
            ->selectRaw('metodo_pago, SUM(monto) as total')
            ->groupBy('metodo_pago')
            ->pluck('total', 'metodo_pago')
            ->toArray();

        // 1. Efectivo
        $this->cierreMontoApertura = (float) $this->sesionActiva->monto_apertura;
        $this->cierreVentasEfectivo = (float) ($ventasDesglose['EFECTIVO'] ?? 0);
        $this->cierreCuotasEfectivo = (float) ($cuotasDesglose['EFECTIVO'] ?? 0);
        $this->cierreEfectivoEsperado = round($this->cierreMontoApertura + $this->cierreVentasEfectivo + $this->cierreCuotasEfectivo, 2);
        $this->cierreEfectivoReal = $this->cierreEfectivoEsperado;

        // 2. Tarjetas (Vouchers POS)
        $this->cierreTarjetaEsperado = round((float) ($ventasDesglose['TARJETA'] ?? 0) + (float) ($cuotasDesglose['TARJETA'] ?? 0), 2);
        $this->cierreTarjetaReal = $this->cierreTarjetaEsperado;

        // 3. Transferencias (Boletas / Depósitos)
        $this->cierreTransfEsperado = round((float) ($ventasDesglose['TRANSFERENCIA'] ?? 0) + (float) ($cuotasDesglose['TRANSFERENCIA'] ?? 0), 2);
        $this->cierreTransfReal = $this->cierreTransfEsperado;

        // 4. Cheques
        $this->cierreChequeEsperado = round((float) ($ventasDesglose['CHEQUE'] ?? 0) + (float) ($cuotasDesglose['CHEQUE'] ?? 0), 2);
        $this->cierreChequeReal = $this->cierreChequeEsperado;

        $this->cierreObservaciones = '';
        $this->recalcularDiferenciasCierre();

        $this->showCerrarCajaModal = true;
    }

    public function updatedCierreEfectivoReal(): void
    {
        $this->recalcularDiferenciasCierre();
    }

    public function updatedCierreTarjetaReal(): void
    {
        $this->recalcularDiferenciasCierre();
    }

    public function updatedCierreTransfReal(): void
    {
        $this->recalcularDiferenciasCierre();
    }

    public function updatedCierreChequeReal(): void
    {
        $this->recalcularDiferenciasCierre();
    }

    public function recalcularDiferenciasCierre(): void
    {
        $efectivoReal = max(0, (float) $this->cierreEfectivoReal);
        $tarjetaReal = max(0, (float) $this->cierreTarjetaReal);
        $transfReal = max(0, (float) $this->cierreTransfReal);
        $chequeReal = max(0, (float) $this->cierreChequeReal);

        $this->cierreDiferenciaEfectivo = round($efectivoReal - $this->cierreEfectivoEsperado, 2);
        $this->cierreDiferenciaTarjeta = round($tarjetaReal - $this->cierreTarjetaEsperado, 2);
        $this->cierreDiferenciaTransf = round($transfReal - $this->cierreTransfEsperado, 2);
        $this->cierreDiferenciaCheque = round($chequeReal - $this->cierreChequeEsperado, 2);

        $this->cierreGranTotalEsperado = round($this->cierreEfectivoEsperado + $this->cierreTarjetaEsperado + $this->cierreTransfEsperado + $this->cierreChequeEsperado, 2);
        $this->cierreGranTotalReal = round($efectivoReal + $tarjetaReal + $transfReal + $chequeReal, 2);
        $this->cierreGranDiferencia = round($this->cierreGranTotalReal - $this->cierreGranTotalEsperado, 2);
    }

    public function confirmarCierreCaja(): void
    {
        if (! $this->sesionActiva) return;

        $this->recalcularDiferenciasCierre();

        $resumenAuditoria = "ARQUEO INTEGRAL:\n"
            . "- Efectivo Gaveta: Esperado Q{$this->cierreEfectivoEsperado} | Real Q{$this->cierreEfectivoReal} (Dif: Q{$this->cierreDiferenciaEfectivo})\n"
            . "- Vouchers Tarjeta: Esperado Q{$this->cierreTarjetaEsperado} | Real Q{$this->cierreTarjetaReal} (Dif: Q{$this->cierreDiferenciaTarjeta})\n"
            . "- Boletas Transf: Esperado Q{$this->cierreTransfEsperado} | Real Q{$this->cierreTransfReal} (Dif: Q{$this->cierreDiferenciaTransf})\n"
            . "- Cheques: Esperado Q{$this->cierreChequeEsperado} | Real Q{$this->cierreChequeReal} (Dif: Q{$this->cierreDiferenciaCheque})";

        if (filled($this->cierreObservaciones)) {
            $resumenAuditoria .= "\nNotas: " . trim($this->cierreObservaciones);
        }

        DB::transaction(function () use ($resumenAuditoria) {
            $this->sesionActiva->update([
                'fecha_cierre' => now(),
                'monto_esperado' => $this->cierreEfectivoEsperado,
                'monto_real' => (float) $this->cierreEfectivoReal,
                'diferencia' => $this->cierreDiferenciaEfectivo,
                'estado' => 'CERRADA',
                'observaciones' => $resumenAuditoria,
            ]);
        });

        $this->showCerrarCajaModal = false;
        $this->sesionActiva = null;

        Notification::make()
            ->title('Turno de Caja Cerrado')
            ->body('Liquidación y arqueo integral completados exitosamente.')
            ->success()
            ->send();
    }
}