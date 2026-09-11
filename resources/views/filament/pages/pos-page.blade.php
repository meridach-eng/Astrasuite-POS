<x-filament-panels::page>
    <style>
        aside.fi-sidebar {
            display: none !important;
        }
        main.fi-main {
            width: 100% !important;
            max-width: 100% !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
        .fi-main-content-start {
            margin-inline-start: 0 !important;
        }
        .astra-pos-container {
            display: flex;
            flex-direction: row;
            gap: 14px;
            height: calc(100vh - 120px);
            min-height: 580px;
            box-sizing: border-box;
            margin-top: -12px;
        }

        .astra-left-panel {
            width: 42%;
            min-width: 380px;
            max-width: 460px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }

        .astra-right-panel {
            flex: 1;
            background: transparent;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-width: 420px;
        }

        .astra-numpad-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            background: #f8fafc;
            padding: 8px;
            border-top: 1px solid #e2e8f0;
            user-select: none;
        }

        .astra-numpad-btn {
            height: 44px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #1e293b;
            transition: all 0.1s ease-in-out;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }

        .astra-numpad-btn:active {
            transform: scale(0.96);
        }

        .astra-numpad-btn.mode-active {
            background: #4F46E5 !important;
            color: #ffffff !important;
            border-color: #4F46E5 !important;
        }

        .astra-numpad-btn.mode-inactive {
            background: #f1f5f9;
            color: #475569;
            font-size: 12px;
        }

        .astra-btn-plusminus {
            background: #e0f2fe !important;
            color: #0369a1 !important;
            border-color: #bae6fd !important;
        }

        .astra-btn-backspace {
            background: #ffe4e6 !important;
            color: #be123c !important;
            border-color: #fecdd3 !important;
        }

        .astra-pay-button {
            width: 100%;
            height: 52px;
            background: #4F46E5;
            color: #ffffff;
            font-size: 16px;
            font-weight: 800;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.15s ease-in-out, transform 0.1s;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }

        .astra-pay-button:hover:not(:disabled) {
            background: #4338ca;
        }

        .astra-pay-button:active:not(:disabled) {
            transform: scale(0.99);
        }

        .astra-pay-button:disabled {
            background: #cbd5e1;
            color: #64748b;
            cursor: not-allowed;
            box-shadow: none;
        }

        .astra-product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 145px));
            grid-auto-rows: 155px;
            gap: 10px;
            overflow-y: auto;
            padding-right: 4px;
            align-content: start;
            flex: 1;
        }

        .astra-product-card {
            width: 100%;
            height: 155px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 6px 8px 8px 8px;
            text-align: left;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
            user-select: none;
        }

        .astra-product-card:hover {
            border-color: #4F46E5;
            box-shadow: 0 6px 14px rgba(79, 70, 229, 0.14);
        }

        .astra-product-card:active {
            transform: scale(0.97);
        }

        .astra-badge-stock {
            position: absolute;
            bottom: 4px;
            right: 4px;
            background: #0f172a;
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            padding: 1px 5px;
            border-radius: 4px;
        }

        .astra-payment-input {
            width: 130px;
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            text-align: right;
            border: 1px solid transparent;
            background: transparent;
            outline: none;
            border-bottom: 2px dashed #94a3b8;
            padding: 2px 4px;
        }

        .astra-payment-input:focus {
            border-bottom: 2px solid #4F46E5;
            background: #ffffff;
            border-radius: 4px;
        }
    </style>

    @if (! $sesionActiva)
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px; text-align: center; max-width: 500px; margin: 40px auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: #e0e7ff; color: #4F46E5; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                <x-heroicon-o-lock-closed style="width: 28px; height: 28px;" />
            </div>
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 8px 0;">No hay turno de caja abierto</h2>
            <p style="color: #64748b; font-size: 13px; margin: 0 0 20px 0;">
                Debes abrir un turno para comenzar a registrar ventas de contado o cobro de cuotas.
            </p>
            <a href="{{ \App\Filament\Resources\SesionCajaResource::getUrl('create') }}" style="display: inline-flex; align-items: center; gap: 8px; background: #4F46E5; color: #ffffff; padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);">
                <x-heroicon-o-key style="width: 18px; height: 18px;" />
                Abrir Turno de Caja
            </a>
        </div>
    @else
        <!-- CAPTURADOR DE TECLADO -->
        <div 
            class="astra-pos-container"
            x-data
            @keydown.window="
                if ($wire.showPaymentModal || $wire.showTicketModal || $wire.showCrearClienteModal || $wire.showCuotasModal || $wire.showReciboCuotaModal || $wire.showCerrarCajaModal) return;

                const tag = $event.target.tagName.toLowerCase();
                if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

                const key = $event.key;

                if (!isNaN(key) && key !== ' ') {
                    $event.preventDefault();
                    @this.pressNumpad(key);
                } else if (key === '.' || key === ',') {
                    $event.preventDefault();
                    @this.pressNumpad('.');
                } else if (key === 'Backspace' || key === 'Delete') {
                    $event.preventDefault();
                    @this.pressNumpad('backspace');
                } else if (key === '+' || key === '-') {
                    $event.preventDefault();
                    @this.pressNumpad('+/-');
                } else if (key === 'Enter') {
                    $event.preventDefault();
                    @this.abrirModalPago();
                }
            "
        >
            
            <!-- PANEL IZQUIERDO: TICKET + CLIENTE + NUMPAD -->
            <div class="astra-left-panel">
                
                <!-- Encabezado de Sesión, Cierre Integral y Cobro de Cuotas -->
                <div style="padding: 8px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="background: #ffffff; border: 1px solid #cbd5e1; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; color: #1e293b;">
                            {{ $sesionActiva->caja->nombre }}
                        </span>
                        <span style="font-size: 11px; color: #64748b; font-weight: 600;">
                            #{{ $sesionActiva->id }}
                        </span>

                        <button 
                            type="button"
                            wire:click="abrirModalCierreCaja"
                            title="Cuadre integral de efectivo, vouchers, depósitos y cheques"
                            style="background: #fee2e2; border: 1px solid #fecdd3; color: #be123c; padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 3px;">
                            <x-heroicon-m-lock-closed style="width: 12px; height: 12px;" />
                            Cerrar Turno
                        </button>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <button 
                            type="button"
                            wire:click="abrirModalCuotas" 
                            title="Cobro de cuotas a clientes"
                            style="background: #eef2ff; border: 1px solid #c7d2fe; color: #4338ca; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                            <x-heroicon-m-credit-card style="width: 14px; height: 14px; color: #4F46E5;" />
                            Cobrar Cuotas
                        </button>

                        @if(count($cart) > 0)
                            <button wire:click="limpiarCarrito" style="background: transparent; border: none; color: #e11d48; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 2px;">
                                <x-heroicon-o-trash style="width: 14px; height: 14px;" />
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Lista de Artículos (Ticket) -->
                <div style="flex: 1; overflow-y: auto; padding: 8px; background: #ffffff;">
                    @forelse ($cart as $idx => $item)
                        <div 
                            wire:click="selectCartItem({{ $idx }})" 
                            style="padding: 8px 10px; border-radius: 8px; margin-bottom: 4px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid {{ $selectedCartIndex === $idx ? '#4F46E5' : 'transparent' }}; background: {{ $selectedCartIndex === $idx ? '#eef2ff' : '#ffffff' }}; transition: background 0.1s;">
                            
                            <div style="flex: 1; min-width: 0; padding-right: 8px;">
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <span style="color: #4F46E5; font-weight: 800; margin-right: 4px;">{{ $item['cantidad'] }}</span> × {{ $item['nombre'] }}
                                </div>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                    Q{{ number_format($item['precio'], 2) }} / unid.
                                    @if ($item['descuento_porcentaje'] > 0)
                                        <span style="color: #e11d48; font-weight: 700; margin-left: 4px;">(-{{ $item['descuento_porcentaje'] }}%)</span>
                                    @endif
                                </div>
                            </div>

                            <div style="text-align: right; font-weight: 800; font-size: 13px; color: #0f172a;">
                                Q{{ number_format($item['subtotal'], 2) }}
                            </div>
                        </div>
                    @empty
                        <div style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; padding: 40px 0;">
                            <x-heroicon-o-shopping-bag style="width: 44px; height: 44px; stroke-width: 1.2; margin-bottom: 8px;" />
                            <div style="font-size: 13px; font-weight: 600;">Ticket Vacío</div>
                            <div style="font-size: 11px; color: #cbd5e1;">Haz clic en los productos para agregarlos</div>
                        </div>
                    @endforelse
                </div>

                <!-- Totales e Impuestos -->
                <div style="padding: 8px 14px; background: #fafafa; border-top: 1px solid #f1f5f9; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between; color: #64748b; font-size: 11px;">
                        <span>{{ $etiquetaImpuesto }}</span>
                        <span>Q{{ number_format($impuestos, 2) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: baseline; font-weight: 800; margin-top: 2px;">
                        <span style="font-size: 14px; color: #334155;">Total</span>
                        <span style="font-size: 24px; color: #0f172a;">Q{{ number_format($total, 2) }}</span>
                    </div>
                </div>

                <!-- Selector de Cliente -->
                <div style="padding: 6px 10px; background: #f8fafc; border-top: 1px solid #e2e8f0; position: relative;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <button 
                            wire:click="toggleBuscadorCliente" 
                            style="flex: 1; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px; text-align: left; cursor: pointer; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                            <div style="overflow: hidden;">
                                <div style="font-size: 12px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $clienteData['nombre'] ?? 'Consumidor Final' }}
                                </div>
                                <div style="font-size: 10px; color: #64748b;">
                                    NIT: <strong style="color: #0f172a;">{{ $clienteData['nit'] ?? 'CF' }}</strong>
                                </div>
                            </div>
                            <x-heroicon-m-user style="width: 16px; height: 16px; color: #94a3b8;" />
                        </button>

                        <button wire:click="$set('showCrearClienteModal', true)" title="Crear Cliente Rápido" style="padding: 8px 10px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; color: #4F46E5; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                            <x-heroicon-o-user-plus style="width: 18px; height: 18px;" />
                        </button>
                    </div>

                    @if ($mostrarBuscadorCliente)
                        <div style="position: absolute; left: 10px; right: 10px; bottom: 58px; z-index: 50; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.12); padding: 8px;">
                            <input
                                type="text"
                                wire:model.live.debounce.200ms="busquedaCliente"
                                placeholder="Escribe NIT o nombre del cliente..."
                                style="width: 100%; font-size: 12px; padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; box-sizing: border-box; margin-bottom: 6px;"
                                autofocus
                            >
                            <div style="max-height: 180px; overflow-y: auto;">
                                @forelse ($listaClientesFiltrados as $cli)
                                    <div wire:click="seleccionarCliente({{ $cli['id'] }})" style="padding: 6px 8px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; font-size: 12px; border-bottom: 1px solid #f1f5f9;">
                                        <div>
                                            <div style="font-weight: 700; color: #0f172a;">{{ $cli['nombre'] }}</div>
                                            <div style="font-size: 10px; color: #64748b;">NIT: <strong style="color: #0f172a;">{{ $cli['nit'] }}</strong></div>
                                        </div>
                                        @if ($cli['id'] === $clienteId)
                                            <span style="color: #4F46E5; font-weight: 800;">✓</span>
                                        @endif
                                    </div>
                                @empty
                                    <div style="padding: 10px; text-align: center; color: #94a3b8; font-size: 11px;">No se encontraron clientes</div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Teclado Numérico -->
                <div class="astra-numpad-grid">
                    <button wire:click="pressNumpad('1')" class="astra-numpad-btn">1</button>
                    <button wire:click="pressNumpad('2')" class="astra-numpad-btn">2</button>
                    <button wire:click="pressNumpad('3')" class="astra-numpad-btn">3</button>
                    <button wire:click="setNumpadMode('cant')" class="astra-numpad-btn {{ $activeNumpadMode === 'cant' ? 'mode-active' : 'mode-inactive' }}">Cant.</button>

                    <button wire:click="pressNumpad('4')" class="astra-numpad-btn">4</button>
                    <button wire:click="pressNumpad('5')" class="astra-numpad-btn">5</button>
                    <button wire:click="pressNumpad('6')" class="astra-numpad-btn">6</button>
                    <button wire:click="setNumpadMode('desc')" class="astra-numpad-btn {{ $activeNumpadMode === 'desc' ? 'mode-active' : 'mode-inactive' }}">% Desc</button>

                    <button wire:click="pressNumpad('7')" class="astra-numpad-btn">7</button>
                    <button wire:click="pressNumpad('8')" class="astra-numpad-btn">8</button>
                    <button wire:click="pressNumpad('9')" class="astra-numpad-btn">9</button>
                    <button wire:click="setNumpadMode('precio')" class="astra-numpad-btn {{ $activeNumpadMode === 'precio' ? 'mode-active' : 'mode-inactive' }}">Precio</button>

                    <button wire:click="pressNumpad('+/-')" class="astra-numpad-btn astra-btn-plusminus">+/-</button>
                    <button wire:click="pressNumpad('0')" class="astra-numpad-btn">0</button>
                    <button wire:click="pressNumpad('.')" class="astra-numpad-btn">.</button>
                    <button wire:click="pressNumpad('backspace')" class="astra-numpad-btn astra-btn-backspace">
                        <x-heroicon-o-backspace style="width: 18px; height: 18px;" />
                    </button>
                </div>

                <!-- Botón PAGO -->
                <div style="padding: 8px 10px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button 
                        wire:click="abrirModalPago" 
                        @disabled(empty($cart) || $total <= 0)
                        class="astra-pay-button">
                        <x-heroicon-o-banknotes style="width: 22px; height: 22px;" />
                        PAGO (Q{{ number_format($total, 2) }})
                    </button>
                </div>
            </div>

            <!-- PANEL DERECHO: CATÁLOGO TÁCTIL -->
            <div class="astra-right-panel">
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 6px 12px; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                    <x-heroicon-o-magnifying-glass style="width: 18px; height: 18px; color: #94a3b8;" />
                    <input
                        type="text"
                        wire:model.live.debounce.250ms="search"
                        wire:keydown.enter="procesarCodigoBarras"
                        placeholder="Buscar o escanear código..."
                        style="width: 100%; border: none; outline: none; background: transparent; font-size: 13px; color: #0f172a; padding: 4px 0;"
                    >
                    @if ($search)
                        <button wire:click="$set('search', '')" style="background: none; border: none; color: #94a3b8; cursor: pointer;">
                            <x-heroicon-o-x-mark style="width: 16px; height: 16px;" />
                        </button>
                    @endif
                </div>

                <!-- Pestañas de Categorías -->
                <div style="display: flex; gap: 6px; overflow-x: auto; padding-bottom: 6px; margin-bottom: 8px;">
                    <button
                        wire:click="seleccionarCategoria(null)"
                        style="padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; white-space: nowrap; cursor: pointer; border: 1px solid {{ is_null($categoriaSeleccionadaId) ? '#4F46E5' : '#cbd5e1' }}; background: {{ is_null($categoriaSeleccionadaId) ? '#4F46E5' : '#ffffff' }}; color: {{ is_null($categoriaSeleccionadaId) ? '#ffffff' : '#475569' }};">
                        Todas
                    </button>
                    @foreach (\App\Models\Categoria::where('activo', true)->get() as $cat)
                        <button
                            wire:click="seleccionarCategoria({{ $cat->id }})"
                            style="padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; white-space: nowrap; cursor: pointer; border: 1px solid {{ $categoriaSeleccionadaId === $cat->id ? '#4F46E5' : '#cbd5e1' }}; background: {{ $categoriaSeleccionadaId === $cat->id ? '#4F46E5' : '#ffffff' }}; color: {{ $categoriaSeleccionadaId === $cat->id ? '#ffffff' : '#475569' }};">
                            {{ $cat->nombre }}
                        </button>
                    @endforeach
                </div>

                <!-- Cuadrícula de Productos -->
                @php
                    $productos = \App\Models\Producto::where('activo', true)
                        ->when($categoriaSeleccionadaId, fn ($q) => $q->where('categoria_id', $categoriaSeleccionadaId))
                        ->when($search, function ($q) {
                            $q->where(function ($sub) {
                                $sub->where('nombre', 'like', "%{$this->search}%")
                                    ->orWhere('codigo_interno', 'like', "%{$this->search}%")
                                    ->orWhere('codigo_barras', 'like', "%{$this->search}%");
                            });
                        })
                        ->get();
                @endphp

                <div class="astra-product-grid">
                    @forelse ($productos as $prod)
                        @php
                            $stock = $prod->stockEnSucursal($sucursalId);
                            $sinStock = $prod->tipo === 'BIEN' && $stock <= 0;
                        @endphp
                        <div
                            wire:click="agregarAlCarrito({{ $prod->id }})"
                            class="astra-product-card"
                            style="{{ $sinStock ? 'opacity: 0.45; cursor: not-allowed;' : '' }}">
                            
                            <div style="width: 100%; height: 90px; background: #f8fafc; border-radius: 8px; margin-bottom: 4px; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                                @if ($prod->imagen)
                                    <img src="{{ asset('storage/' . $prod->imagen) }}" alt="{{ $prod->nombre }}" style="width: 100%; height: 100%; object-fit: contain; padding: 4px;">
                                @else
                                    <x-heroicon-o-photo style="width: 30px; height: 30px; color: #cbd5e1;" />
                                @endif

                                @if ($prod->tipo === 'BIEN')
                                    <span class="astra-badge-stock" style="{{ $stock <= 0 ? 'background: #e11d48;' : '' }}">
                                        {{ $stock }}
                                    </span>
                                @endif
                            </div>

                            <div style="flex: 1; min-height: 28px;">
                                <div style="font-size: 11px; font-weight: 700; color: #0f172a; line-height: 1.15; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    {{ $prod->nombre }}
                                </div>
                            </div>

                            <div style="margin-top: 2px; padding-top: 2px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 12px; font-weight: 800; color: #0f172a;">
                                    Q{{ number_format($prod->precio_venta, 2) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div style="grid-column: 1 / -1; text-align: center; color: #94a3b8; padding: 60px 0; font-size: 13px;">
                            No hay productos disponibles.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- MODAL 1: REGISTRO RÁPIDO DE CLIENTE -->
        @if ($showCrearClienteModal)
            <div style="position: fixed; inset: 0; z-index: 60; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 16px;">
                <div style="background: #ffffff; border-radius: 14px; width: 100%; max-width: 420px; padding: 22px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 14px;">
                        <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">Nuevo Cliente</h3>
                        <button wire:click="$set('showCrearClienteModal', false)" style="background: none; border: none; cursor: pointer; color: #94a3b8;">
                            <x-heroicon-o-x-mark style="width: 20px; height: 20px;" />
                        </button>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 12px;">
                        <div>
                            <label style="font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">NIT, CUI o Identificación *</label>
                            <div style="display: flex; gap: 6px;">
                                <input type="text" wire:model="nuevoNumeroDoc" placeholder="NIT o 13 dígitos de DPI" style="flex: 1; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 12px;">
                                <button type="button" wire:click="consultarNitEnFelplex" style="padding: 8px 12px; background: #4F46E5; color: #ffffff; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 12px; white-space: nowrap;">
                                    Consultar SAT
                                </button>
                            </div>
                        </div>
                        <div>
                            <label style="font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Nombre / Razón Social *</label>
                            <input type="text" wire:model="nuevoNombre" placeholder="Ej. María García" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 12px;">
                        </div>
                        <div>
                            <label style="font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Dirección Fiscal</label>
                            <input type="text" wire:model="nuevoDireccion" placeholder="Ej. Guatemala" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 12px;">
                        </div>
                        <div>
                            <label style="font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Teléfono</label>
                            <input type="text" wire:model="nuevoTelefono" placeholder="Ej. 55551234" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 12px;">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
                        <button wire:click="$set('showCrearClienteModal', false)" style="padding: 8px 14px; background: transparent; border: none; font-weight: 700; color: #64748b; cursor: pointer; font-size: 12px;">Cancelar</button>
                        <button wire:click="guardarClienteRapido" style="padding: 8px 16px; background: #4F46E5; color: #ffffff; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 12px;">Guardar Cliente</button>
                    </div>
                </div>
            </div>
        @endif

        <!-- MODAL 2: GESTIÓN DE COBRO DE CUOTAS INTEGRADO AL POS -->
        @if ($showCuotasModal)
            <div style="position: fixed; inset: 0; z-index: 75; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 16px;">
                <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 960px; height: 620px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column; overflow: hidden; border: 1px solid #cbd5e1;">
                    
                    <div style="height: 52px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0 18px; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #4F46E5; color: #ffffff; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 800;">
                                Cobro de Créditos
                            </span>
                            <span style="font-size: 13px; font-weight: 700; color: #0f172a;">
                                Caja Activa: {{ $sesionActiva->caja->nombre }} (Turno #{{ $sesionActiva->id }})
                            </span>
                        </div>
                        <button wire:click="$set('showCuotasModal', false)" style="background: none; border: none; cursor: pointer; color: #94a3b8;">
                            <x-heroicon-o-x-mark style="width: 22px; height: 22px;" />
                        </button>
                    </div>

                    <div style="flex: 1; display: flex; overflow: hidden;">
                        
                        <!-- Columna Izquierda: Lista de Contratos -->
                        <div style="width: 340px; background: #f8fafc; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; padding: 12px;">
                            <div style="margin-bottom: 10px; position: relative;">
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.250ms="busquedaCuota"
                                    placeholder="Buscar por cliente o INS-..."
                                    style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12px; box-sizing: border-box; background: #ffffff;"
                                >
                            </div>

                            <div style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 6px;">
                                @forelse ($contratosEncontrados as $c)
                                    <div 
                                        wire:click="seleccionarContrato({{ $c['id'] }})"
                                        style="padding: 10px 12px; border-radius: 8px; cursor: pointer; border: 1px solid {{ ($contratoActivo['id'] ?? null) === $c['id'] ? '#4F46E5' : '#e2e8f0' }}; background: {{ ($contratoActivo['id'] ?? null) === $c['id'] ? '#eef2ff' : '#ffffff' }}; transition: all 0.1s;">
                                        
                                        <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                            <span style="font-weight: 800; font-size: 12px; color: #4F46E5;">{{ $c['referencia'] }}</span>
                                            <span style="font-size: 10px; background: #fee2e2; color: #b91c1c; font-weight: 700; padding: 1px 6px; border-radius: 4px;">
                                                {{ $c['cuotas_pendientes'] }} pend.
                                            </span>
                                        </div>

                                        <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-top: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $c['cliente'] }}
                                        </div>

                                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b; margin-top: 4px;">
                                            <span>NIT: {{ $c['doc'] }}</span>
                                            <span style="font-weight: 800; color: #e11d48;">Saldo: Q{{ number_format($c['saldo'], 2) }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div style="text-align: center; color: #94a3b8; font-size: 12px; padding: 40px 10px;">
                                        No se encontraron contratos con saldo pendiente.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Columna Derecha: Cronograma y Cobro -->
                        <div style="flex: 1; display: flex; flex-direction: column; padding: 16px; overflow-y: auto; background: #ffffff;">
                            @if ($contratoActivo)
                                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 12px;">
                                    <div>
                                        <h4 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;">
                                            {{ $contratoActivo['referencia'] }} — {{ $contratoActivo['cliente'] }}
                                        </h4>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                            Total Contrato: Q{{ number_format($contratoActivo['total'], 2) }} &nbsp;|&nbsp; 
                                            <strong style="color: #e11d48;">Saldo Restante: Q{{ number_format($contratoActivo['saldo_pendiente'], 2) }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 16px;">
                                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                                        <thead>
                                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left; color: #475569;">
                                                <th style="padding: 8px 10px;"># Letra</th>
                                                <th style="padding: 8px 10px;">Vencimiento</th>
                                                <th style="padding: 8px 10px; text-align: right;">Cuota</th>
                                                <th style="padding: 8px 10px; text-align: right;">Saldo</th>
                                                <th style="padding: 8px 10px; text-align: center;">Estado</th>
                                                <th style="padding: 8px 10px; text-align: center;">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($contratoActivo['cuotas'] as $cuota)
                                                <tr style="border-bottom: 1px solid #f1f5f9; background: {{ $cuotaSeleccionadaId === $cuota['id'] ? '#f0fdf4' : 'transparent' }};">
                                                    <td style="padding: 8px 10px; font-weight: 700;">Cuota #{{ $cuota['numero'] }}</td>
                                                    <td style="padding: 8px 10px; color: {{ $cuota['vencida'] ? '#b91c1c' : '#334155' }}; font-weight: {{ $cuota['vencida'] ? '800' : 'normal' }};">
                                                        {{ $cuota['vencimiento'] }}
                                                        @if ($cuota['vencida'])
                                                            <span style="font-size: 10px; color: #b91c1c; font-weight: 800;">(Vencida)</span>
                                                        @endif
                                                    </td>
                                                    <td style="padding: 8px 10px; text-align: right;">Q{{ number_format($cuota['monto'], 2) }}</td>
                                                    <td style="padding: 8px 10px; text-align: right; font-weight: 800; color: {{ $cuota['saldo'] > 0 ? '#e11d48' : '#059669' }};">
                                                        Q{{ number_format($cuota['saldo'], 2) }}
                                                    </td>
                                                    <td style="padding: 8px 10px; text-align: center;">
                                                        <span style="padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 700; background: {{ $cuota['estado'] === 'PAGADO' ? '#dcfce7' : ($cuota['estado'] === 'PARCIAL' ? '#fef3c7' : '#fee2e2') }}; color: {{ $cuota['estado'] === 'PAGADO' ? '#15803d' : ($cuota['estado'] === 'PARCIAL' ? '#b45309' : '#b91c1c') }};">
                                                            {{ $cuota['estado'] }}
                                                        </span>
                                                    </td>
                                                    <td style="padding: 8px 10px; text-align: center;">
                                                        @if ($cuota['saldo'] > 0)
                                                            <button 
                                                                wire:click="prepararCobroCuota({{ $cuota['id'] }})"
                                                                style="background: #059669; color: #ffffff; border: none; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;">
                                                                Cobrar
                                                            </button>
                                                        @else
                                                            <span style="color: #059669; font-weight: 800; font-size: 12px;">✓ Saldada</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if ($cuotaSeleccionada)
                                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 14px; margin-top: auto;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                            <div style="font-weight: 800; font-size: 13px; color: #0f172a;">
                                                Abonar a Cuota #{{ $cuotaSeleccionada['numero'] }} (Saldo Pendiente: Q{{ number_format($cuotaSeleccionada['saldo_pendiente'], 2) }})
                                            </div>
                                            <button wire:click="$set('cuotaSeleccionada', null)" style="background: none; border: none; color: #64748b; font-size: 12px; cursor: pointer;">
                                                Cancelar
                                            </button>
                                        </div>

                                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: flex-end;">
                                            <div>
                                                <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Monto a Abonar (Q) *</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01" 
                                                    wire:model="montoAbonoCuota"
                                                    style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-weight: 800; box-sizing: border-box;"
                                                >
                                            </div>

                                            <div>
                                                <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Método de Pago *</label>
                                                <select 
                                                    wire:model="metodoPagoCuota"
                                                    style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; box-sizing: border-box; background: #ffffff;">
                                                    <option value="EFECTIVO">Efectivo (Gaveta)</option>
                                                    <option value="TARJETA">Tarjeta POS</option>
                                                    <option value="TRANSFERENCIA">Transferencia / Depósito</option>
                                                    <option value="CHEQUE">Cheque</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">No. Boleta / Ref.</label>
                                                <input 
                                                    type="text" 
                                                    wire:model="referenciaPagoCuota"
                                                    placeholder="Opcional..."
                                                    style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; box-sizing: border-box;"
                                                >
                                            </div>
                                        </div>

                                        <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px;">
                                            <button 
                                                type="button"
                                                wire:click="$set('montoAbonoCuota', {{ $cuotaSeleccionada['saldo_pendiente'] }})"
                                                style="padding: 6px 12px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;">
                                                Cobrar Cuota Completa (Q{{ number_format($cuotaSeleccionada['saldo_pendiente'], 2) }})
                                            </button>

                                            <button 
                                                type="button"
                                                wire:click="procesarAbonoCuota"
                                                style="padding: 8px 18px; background: #059669; color: #ffffff; border: none; border-radius: 6px; font-size: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 2px 4px rgba(5,150,105,0.3);">
                                                Confirmar e Ingresar a Caja
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8;">
                                    <x-heroicon-o-document-text style="width: 48px; height: 48px; stroke-width: 1.2; margin-bottom: 8px;" />
                                    <div style="font-size: 13px; font-weight: 600;">Selecciona un contrato de la lista izquierda</div>
                                    <div style="font-size: 11px; color: #cbd5e1;">Visualiza el cronograma y cobra las cuotas de forma instantánea</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- MODAL 3: RECIBO DE PAGO DE CUOTA EXITOSO -->
        @if ($showReciboCuotaModal && $ultimoPagoInfo)
            <div style="position: fixed; inset: 0; z-index: 80; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 16px;">
                <div style="background: #ffffff; border-radius: 14px; width: 100%; max-width: 380px; padding: 22px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3); border: 1px solid #cbd5e1;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto;">
                        <x-heroicon-o-check style="width: 26px; height: 26px;" />
                    </div>
                    <h3 style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0;">¡Cobro Registrado!</h3>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px; margin-bottom: 14px;">
                        {{ $ultimoPagoInfo['contrato'] }} — Cuota #{{ $ultimoPagoInfo['numero_cuota'] }}
                    </div>

                    <div style="background: #f8fafc; border-radius: 8px; padding: 12px; font-family: monospace; font-size: 12px; text-align: left; margin-bottom: 16px; display: flex; flex-direction: column; gap: 5px; border: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between;"><span>Cliente:</span><strong style="color: #0f172a;">{{ $ultimoPagoInfo['cliente'] }}</strong></div>
                        <div style="display: flex; justify-content: space-between;"><span>Método:</span><span>{{ $ultimoPagoInfo['metodo_pago'] }}</span></div>
                        <div style="display: flex; justify-content: space-between; font-weight: 800; color: #059669; font-size: 14px;">
                            <span>Abonado:</span><span>Q{{ number_format($ultimoPagoInfo['monto_abonado'], 2) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; color: #e11d48;">
                            <span>Resta en Cuota:</span><span>Q{{ number_format($ultimoPagoInfo['saldo_cuota'], 2) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 4px; font-weight: 700;">
                            <span>Saldo Contrato:</span><span>Q{{ number_format($ultimoPagoInfo['saldo_contrato'], 2) }}</span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button 
                            type="button"
                            onclick="window.open('/cuota-cobros/imprimir/{{ $ultimoPagoInfo['id'] }}', 'ReciboTermico', 'width=380,height=650,toolbar=no,menubar=no,location=no,status=no')"
                            style="flex: 1; padding: 10px; background: #059669; color: #ffffff; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 2px 4px rgba(5,150,105,0.3);">
                            <x-heroicon-o-printer style="width: 16px; height: 16px;" />
                            Imprimir 80mm
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('showReciboCuotaModal', false)"
                            style="flex: 1; padding: 10px; background: #e2e8f0; color: #0f172a; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 12px;">
                            Continuar
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- MODAL 4: ARQUEO Y CIERRE INTEGRAL (EFECTIVO + TARJETA + TRANSFERENCIA + CHEQUES) -->
        @if ($showCerrarCajaModal)
            <div style="position: fixed; inset: 0; z-index: 85; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 16px;">
                <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 680px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); border: 1px solid #cbd5e1;">
                    
                    <!-- Header -->
                    <div style="height: 52px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0 20px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Cuadre y Cierre Integral de Caja</h3>
                            <div style="font-size: 11px; color: #64748b;">
                                {{ $sesionActiva->caja->nombre }} — Turno #{{ $sesionActiva->id }} ({{ now()->format('d/m/Y') }})
                            </div>
                        </div>
                        <button wire:click="$set('showCerrarCajaModal', false)" style="background: none; border: none; cursor: pointer; color: #94a3b8;">
                            <x-heroicon-o-x-mark style="width: 22px; height: 22px;" />
                        </button>
                    </div>

                    <!-- Cuerpo con Scroll -->
                    <div style="flex: 1; overflow-y: auto; padding: 18px 20px;">
                        
                        <!-- 1. SECCIÓN EFECTIVO -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-weight: 800; font-size: 13px; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                    <x-heroicon-o-banknotes style="width: 18px; height: 18px; color: #059669;" />
                                    1. Efectivo Físico en Gaveta
                                </span>
                                <span style="font-size: 11px; color: #64748b;">
                                    Fondo: Q{{ number_format($cierreMontoApertura, 2) }} | POS: Q{{ number_format($cierreVentasEfectivo, 2) }} | Cuotas: Q{{ number_format($cierreCuotasEfectivo, 2) }}
                                </span>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: center;">
                                <div>
                                    <span style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700;">Esperado</span>
                                    <div style="font-size: 16px; font-weight: 800; color: #0f172a;">Q{{ number_format($cierreEfectivoEsperado, 2) }}</div>
                                </div>
                                <div>
                                    <span style="font-size: 10px; color: #334155; text-transform: uppercase; font-weight: 700;">Contado Real (Q) *</span>
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        wire:model.live.debounce.250ms="cierreEfectivoReal"
                                        onclick="this.select()"
                                        style="width: 100%; padding: 6px 8px; font-size: 15px; font-weight: 800; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; box-sizing: border-box;"
                                    >
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700;">Diferencia</span>
                                    <div style="font-size: 14px; font-weight: 800; color: {{ $cierreDiferenciaEfectivo == 0 ? '#16a34a' : ($cierreDiferenciaEfectivo > 0 ? '#2563eb' : '#dc2626') }};">
                                        {{ $cierreDiferenciaEfectivo > 0 ? '+' : '' }}Q{{ number_format($cierreDiferenciaEfectivo, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. SECCIÓN TARJETA (VOUCHERS) -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-weight: 800; font-size: 13px; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                    <x-heroicon-o-credit-card style="width: 18px; height: 18px; color: #2563eb;" />
                                    2. Vouchers de Tarjeta (Lote POS)
                                </span>
                                <span style="font-size: 11px; color: #64748b;">Suma total de tickets de tarjeta</span>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: center;">
                                <div>
                                    <span style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700;">Esperado</span>
                                    <div style="font-size: 16px; font-weight: 800; color: #0f172a;">Q{{ number_format($cierreTarjetaEsperado, 2) }}</div>
                                </div>
                                <div>
                                    <span style="font-size: 10px; color: #334155; text-transform: uppercase; font-weight: 700;">Suma Vouchers (Q) *</span>
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        wire:model.live.debounce.250ms="cierreTarjetaReal"
                                        onclick="this.select()"
                                        style="width: 100%; padding: 6px 8px; font-size: 15px; font-weight: 800; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; box-sizing: border-box;"
                                    >
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700;">Diferencia</span>
                                    <div style="font-size: 14px; font-weight: 800; color: {{ $cierreDiferenciaTarjeta == 0 ? '#16a34a' : ($cierreDiferenciaTarjeta > 0 ? '#2563eb' : '#dc2626') }};">
                                        {{ $cierreDiferenciaTarjeta > 0 ? '+' : '' }}Q{{ number_format($cierreDiferenciaTarjeta, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. SECCIÓN TRANSFERENCIAS (BOLETAS) -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-weight: 800; font-size: 13px; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                    <x-heroicon-o-arrows-right-left style="width: 18px; height: 18px; color: #4F46E5;" />
                                    3. Boletas de Transferencia / Depósito
                                </span>
                                <span style="font-size: 11px; color: #64748b;">Suma de comprobantes bancarios</span>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: center;">
                                <div>
                                    <span style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700;">Esperado</span>
                                    <div style="font-size: 16px; font-weight: 800; color: #0f172a;">Q{{ number_format($cierreTransfEsperado, 2) }}</div>
                                </div>
                                <div>
                                    <span style="font-size: 10px; color: #334155; text-transform: uppercase; font-weight: 700;">Suma Boletas (Q) *</span>
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        wire:model.live.debounce.250ms="cierreTransfReal"
                                        onclick="this.select()"
                                        style="width: 100%; padding: 6px 8px; font-size: 15px; font-weight: 800; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; box-sizing: border-box;"
                                    >
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700;">Diferencia</span>
                                    <div style="font-size: 14px; font-weight: 800; color: {{ $cierreDiferenciaTransf == 0 ? '#16a34a' : ($cierreDiferenciaTransf > 0 ? '#2563eb' : '#dc2626') }};">
                                        {{ $cierreDiferenciaTransf > 0 ? '+' : '' }}Q{{ number_format($cierreDiferenciaTransf, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. SECCIÓN CHEQUES -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 14px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-weight: 800; font-size: 13px; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                    <x-heroicon-o-document-text style="width: 18px; height: 18px; color: #0284c7;" />
                                    4. Cheques Recibidos
                                </span>
                                <span style="font-size: 11px; color: #64748b;">Suma de cheques en mano</span>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: center;">
                                <div>
                                    <span style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700;">Esperado</span>
                                    <div style="font-size: 16px; font-weight: 800; color: #0f172a;">Q{{ number_format($cierreChequeEsperado, 2) }}</div>
                                </div>
                                <div>
                                    <span style="font-size: 10px; color: #334155; text-transform: uppercase; font-weight: 700;">Suma Cheques (Q) *</span>
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        wire:model.live.debounce.250ms="cierreChequeReal"
                                        onclick="this.select()"
                                        style="width: 100%; padding: 6px 8px; font-size: 15px; font-weight: 800; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; box-sizing: border-box;"
                                    >
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700;">Diferencia</span>
                                    <div style="font-size: 14px; font-weight: 800; color: {{ $cierreDiferenciaCheque == 0 ? '#16a34a' : ($cierreDiferenciaCheque > 0 ? '#2563eb' : '#dc2626') }};">
                                        {{ $cierreDiferenciaCheque > 0 ? '+' : '' }}Q{{ number_format($cierreDiferenciaCheque, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TOTAL INTEGRAL Y CUADRE GENERAL -->
                        <div style="padding: 12px; border-radius: 10px; background: {{ $cierreGranDiferencia == 0 ? '#f0fdf4' : ($cierreGranDiferencia > 0 ? '#eff6ff' : '#fef2f2') }}; border: 1px solid {{ $cierreGranDiferencia == 0 ? '#bbf7d0' : ($cierreGranDiferencia > 0 ? '#bfdbfe' : '#fecaca') }}; margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Turno Consolidado</div>
                                    <div style="font-size: 13px; font-weight: 800; color: #0f172a;">
                                        Esperado: Q{{ number_format($cierreGranTotalEsperado, 2) }} &nbsp;|&nbsp; Entregado: Q{{ number_format($cierreGranTotalReal, 2) }}
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 11px; font-weight: 700; color: #64748b;">Balance General:</div>
                                    <div style="font-size: 18px; font-weight: 900; color: {{ $cierreGranDiferencia == 0 ? '#16a34a' : ($cierreGranDiferencia > 0 ? '#2563eb' : '#dc2626') }};">
                                        {{ $cierreGranDiferencia > 0 ? '+' : '' }}Q{{ number_format($cierreGranDiferencia, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div>
                            <label style="font-size: 11px; font-weight: 700; color: #64748b; display: block; margin-bottom: 4px;">Observaciones del Cajero</label>
                            <textarea 
                                wire:model="cierreObservaciones" 
                                rows="2" 
                                placeholder="Anotaciones sobre cheques, depósitos o diferencias..."
                                style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; box-sizing: border-box;"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div style="height: 58px; background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 0 20px; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                        <button 
                            type="button" 
                            wire:click="$set('showCerrarCajaModal', false)"
                            style="padding: 9px 16px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 700; color: #64748b; font-size: 12px; cursor: pointer;">
                            Cancelar
                        </button>
                        <button 
                            type="button" 
                            wire:click="confirmarCierreCaja"
                            style="padding: 9px 20px; background: #dc2626; color: #ffffff; border: none; border-radius: 8px; font-weight: 800; font-size: 12px; cursor: pointer; box-shadow: 0 2px 4px rgba(220,38,38,0.25);">
                            Finalizar y Cerrar Turno
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- MODAL 5: PANTALLA DE COBRO POS -->
        @if ($showPaymentModal)
            <div 
                style="position: fixed; inset: 0; z-index: 70; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 20px;"
                x-data
                @keydown.window="
                    if (!$wire.showPaymentModal) return;
                    const tag = $event.target.tagName.toLowerCase();
                    if (tag === 'input' && $event.target.classList.contains('astra-payment-input')) return;

                    const key = $event.key;
                    if (!isNaN(key) && key !== ' ') {
                        $event.preventDefault();
                        $wire.pressPagoNumpad(key);
                    } else if (key === '.' || key === ',') {
                        $event.preventDefault();
                        $wire.pressPagoNumpad('.');
                    } else if (key === 'Backspace' || key === 'Delete') {
                        $event.preventDefault();
                        $wire.pressPagoNumpad('backspace');
                    } else if (key === 'Escape') {
                        $event.preventDefault();
                        $wire.set('showPaymentModal', false);
                    }
                "
            >
                <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 820px; height: 530px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column; overflow: hidden; border: 1px solid #cbd5e1;">
                    
                    <div style="height: 48px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0 16px; display: flex; align-items: center; justify-content: space-between;">
                        <div style="font-weight: 800; font-size: 14px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <span>Cobro de Contado</span>
                            <span style="font-size: 12px; color: #64748b; font-weight: 600;">• {{ $clienteData['nombre'] ?? 'CF' }} (NIT: {{ $clienteData['nit'] ?? 'CF' }})</span>
                        </div>
                        <button wire:click="$set('showPaymentModal', false)" style="background: none; border: none; cursor: pointer; color: #94a3b8;">
                            <x-heroicon-o-x-mark style="width: 20px; height: 20px;" />
                        </button>
                    </div>

                    <div style="flex: 1; display: flex; overflow: hidden;">
                        <div style="width: 320px; background: #f8fafc; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; padding: 10px; justify-content: space-between;">
                            
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                    <button wire:click="agregarMetodoPago('EFECTIVO')" style="padding: 10px 8px; border-radius: 8px; font-size: 12px; font-weight: 800; border: 1px solid #cbd5e1; background: #ffffff; color: #0f172a; cursor: pointer; display: flex; align-items: center; gap: 6px; justify-content: center;">
                                        <x-heroicon-o-banknotes style="width: 18px; height: 18px; color: #059669;" />
                                        Efectivo
                                    </button>
                                    <button wire:click="agregarMetodoPago('TARJETA')" style="padding: 10px 8px; border-radius: 8px; font-size: 12px; font-weight: 800; border: 1px solid #cbd5e1; background: #ffffff; color: #0f172a; cursor: pointer; display: flex; align-items: center; gap: 6px; justify-content: center;">
                                        <x-heroicon-o-credit-card style="width: 18px; height: 18px; color: #2563eb;" />
                                        Tarjeta
                                    </button>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                    <button wire:click="agregarMetodoPago('TRANSFERENCIA')" style="padding: 8px; border-radius: 8px; font-size: 11px; font-weight: 700; border: 1px solid #cbd5e1; background: #ffffff; color: #0f172a; cursor: pointer; display: flex; align-items: center; gap: 4px; justify-content: center;">
                                        <x-heroicon-o-arrows-right-left style="width: 16px; height: 16px; color: #4F46E5;" />
                                        Transferencia
                                    </button>
                                    <button wire:click="agregarMetodoPago('CHEQUE')" style="padding: 8px; border-radius: 8px; font-size: 11px; font-weight: 700; border: 1px solid #cbd5e1; background: #ffffff; color: #0f172a; cursor: pointer; display: flex; align-items: center; gap: 4px; justify-content: center;">
                                        <x-heroicon-o-document-text style="width: 16px; height: 16px; color: #0284c7;" />
                                        Cheque
                                    </button>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; user-select: none;">
                                <button wire:click="pressPagoNumpad('1')" class="astra-numpad-btn" style="height: 40px;">1</button>
                                <button wire:click="pressPagoNumpad('2')" class="astra-numpad-btn" style="height: 40px;">2</button>
                                <button wire:click="pressPagoNumpad('3')" class="astra-numpad-btn" style="height: 40px;">3</button>
                                <button wire:click="sumarBilleteRapido(10)" class="astra-numpad-btn" style="height: 40px; background: #dbeafe; color: #1d4ed8; font-weight: 800; border-color: #bfdbfe;">+10</button>

                                <button wire:click="pressPagoNumpad('4')" class="astra-numpad-btn" style="height: 40px;">4</button>
                                <button wire:click="pressPagoNumpad('5')" class="astra-numpad-btn" style="height: 40px;">5</button>
                                <button wire:click="pressPagoNumpad('6')" class="astra-numpad-btn" style="height: 40px;">6</button>
                                <button wire:click="sumarBilleteRapido(20)" class="astra-numpad-btn" style="height: 40px; background: #dbeafe; color: #1d4ed8; font-weight: 800; border-color: #bfdbfe;">+20</button>

                                <button wire:click="pressPagoNumpad('7')" class="astra-numpad-btn" style="height: 40px;">7</button>
                                <button wire:click="pressPagoNumpad('8')" class="astra-numpad-btn" style="height: 40px;">8</button>
                                <button wire:click="pressPagoNumpad('9')" class="astra-numpad-btn" style="height: 40px;">9</button>
                                <button wire:click="sumarBilleteRapido(50)" class="astra-numpad-btn" style="height: 40px; background: #dbeafe; color: #1d4ed8; font-weight: 800; border-color: #bfdbfe;">+50</button>

                                <button wire:click="pressPagoNumpad('+/-')" class="astra-numpad-btn astra-btn-plusminus" style="height: 40px;">+/-</button>
                                <button wire:click="pressPagoNumpad('0')" class="astra-numpad-btn">0</button>
                                <button wire:click="pressPagoNumpad('.')" class="astra-numpad-btn">.</button>
                                <button wire:click="pressPagoNumpad('backspace')" class="astra-numpad-btn astra-btn-backspace" style="height: 40px;">
                                    <x-heroicon-o-backspace style="width: 16px; height: 16px;" />
                                </button>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                <button wire:click="$set('showPaymentModal', false)" style="height: 42px; border: 1px solid #cbd5e1; background: #ffffff; border-radius: 8px; font-weight: 700; color: #64748b; font-size: 13px; cursor: pointer;">
                                    Regresar
                                </button>
                                <button 
                                    wire:click="procesarVenta"
                                    @disabled($restante > 0)
                                    style="height: 42px; border: none; border-radius: 8px; font-weight: 800; font-size: 13px; color: #ffffff; cursor: {{ $restante > 0 ? 'not-allowed' : 'pointer' }}; background: {{ $restante > 0 ? '#94a3b8' : '#059669' }};">
                                    Validar
                                </button>
                            </div>
                        </div>

                        <div style="flex: 1; padding: 24px; display: flex; flex-direction: column; justify-content: flex-start; overflow-y: auto;">
                            <div style="text-align: center; margin-bottom: 24px;">
                                <div style="font-size: 52px; font-weight: 900; color: #0f172a; line-height: 1;">
                                    {{ number_format($total, 2) }}<span style="font-size: 26px; font-weight: 700; color: #64748b; margin-left: 4px;">Q</span>
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                                @foreach ($lineasPago as $idx => $linea)
                                    <div 
                                        wire:click="seleccionarLineaPago({{ $idx }})"
                                        style="border: 2px solid {{ $pagoActivoIndex === $idx ? '#4F46E5' : '#e2e8f0' }}; background: {{ $pagoActivoIndex === $idx ? '#eef2ff' : '#ffffff' }}; border-radius: 10px; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                        
                                        <div style="font-weight: 700; font-size: 14px; color: #0f172a; text-transform: capitalize;">
                                            {{ strtolower($linea['metodo']) }}
                                        </div>

                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input
                                                type="number"
                                                step="0.01"
                                                value="{{ $linea['monto'] }}"
                                                wire:change="actualizarMontoLineaPago({{ $idx }}, $event.target.value)"
                                                wire:keyup.enter="actualizarMontoLineaPago({{ $idx }}, $event.target.value)"
                                                class="astra-payment-input"
                                                onclick="this.select()"
                                            >
                                            <span style="font-size: 13px; font-weight: 700; color: #64748b;">Q</span>

                                            @if (count($lineasPago) > 1)
                                                <button wire:click.stop="eliminarLineaPago({{ $idx }})" style="background: none; border: none; color: #e11d48; cursor: pointer; padding: 2px;">
                                                    <x-heroicon-o-x-mark style="width: 18px; height: 18px;" />
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: auto;">
                                @if ($restante > 0)
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <span style="font-size: 16px; font-weight: 700; color: #e11d48;">Restante por cobrar</span>
                                            <div style="font-size: 11px; color: #94a3b8;">Pago incompleto (solo contado)</div>
                                        </div>
                                        <span style="font-size: 22px; font-weight: 900; color: #e11d48;">
                                            {{ number_format($restante, 2) }} <span style="font-size: 13px;">Q</span>
                                        </span>
                                    </div>
                                @else
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <span style="font-size: 16px; font-weight: 700; color: #059669;">Cambio / Vuelto</span>
                                            <div style="font-size: 11px; color: #059669;">Entregar al cliente</div>
                                        </div>
                                        <span style="font-size: 24px; font-weight: 900; color: #059669;">
                                            {{ number_format($cambio, 2) }} <span style="font-size: 13px;">Q</span>
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- MODAL 6: RECIBO O TICKET FELPLEX -->
        @if ($showTicketModal && $ultimaVenta)
            <div style="position: fixed; inset: 0; z-index: 70; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 16px;">
                <div style="background: #ffffff; border-radius: 14px; width: 100%; max-width: 380px; padding: 22px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.25); border: 1px solid #cbd5e1;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto;">
                        <x-heroicon-o-check style="width: 24px; height: 24px;" />
                    </div>
                    
                    <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">
                        {{ filled($ultimaVenta->fel_uuid) ? '¡Factura Electrónica Certificada!' : '¡Venta Exitosa!' }}
                    </h3>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 12px;">
                        Ticket: {{ $ultimaVenta->numero_ticket }}
                        @if(filled($ultimaVenta->fel_uuid))
                            <br><span style="color: #4F46E5; font-weight: 700;">UUID SAT: {{ Str::limit($ultimaVenta->fel_uuid, 18) }}</span>
                        @endif
                    </div>

                    <div style="background: #f8fafc; border-radius: 8px; padding: 10px; font-family: monospace; font-size: 12px; text-align: left; margin-bottom: 16px; display: flex; flex-direction: column; gap: 4px; border: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between;"><span>Cliente:</span><strong>{{ $ultimaVenta->cliente->nombre ?? 'CF' }}</strong></div>
                        <div style="display: flex; justify-content: space-between;"><span>Total:</span><strong>Q{{ number_format($ultimaVenta->total, 2) }}</strong></div>
                        <div style="display: flex; justify-content: space-between;"><span>Cobrado:</span><span>Q{{ number_format($ultimaVenta->monto_pagado, 2) }}</span></div>
                        <div style="display: flex; justify-content: space-between; color: #059669; font-weight: 800;"><span>Cambio:</span><span>Q{{ number_format($ultimaVenta->cambio_entregado, 2) }}</span></div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @if ($felHabilitadoGlobal)
                            @if(filled($ultimaVenta->fel_uuid))
                                <!-- Botón para imprimir formato ticket (80mm) certificado desde FELplex (/text/{uuid}) -->
                                <a 
                                    href="https://felplex-gt.stage.plex.lat/text/{{ $ultimaVenta->fel_uuid }}" 
                                    target="_blank"
                                    style="width: 100%; padding: 10px; background: #059669; color: #ffffff; border-radius: 8px; font-weight: 800; text-decoration: none; font-size: 12px; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 2px 4px rgba(5,150,105,0.2);">
                                    <x-heroicon-o-printer style="width: 16px; height: 16px;" />
                                    Imprimir Ticket 80mm (SAT)
                                </a>
                            @else
                                <!-- Opción 1: Crear ticket sin certificar -->
                                <button 
                                    type="button"
                                    onclick="window.open('{{ route('pos.ticket.imprimir', $ultimaVenta->id) }}', 'TicketTermico', 'width=380,height=650,toolbar=no,menubar=no,location=no,status=no')"
                                    style="width: 100%; padding: 10px; background: #e2e8f0; border: none; border-radius: 8px; font-weight: 800; color: #0f172a; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <x-heroicon-o-printer style="width: 16px; height: 16px; color: #4F46E5;" />
                                    Crear Ticket (Sin Certificar)
                                </button>

                                <!-- Opción 2: Certificar manualmente en el momento -->
                                <button 
                                    type="button"
                                    wire:click="certificarVentaActual"
                                    style="width: 100%; padding: 10px; background: #4F46E5; color: #ffffff; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <x-heroicon-o-shield-check style="width: 16px; height: 16px;" />
                                    Certificar Factura (FELplex)
                                </button>
                            @endif
                        @else
                            <!-- Si FELplex está deshabilitado: Solo opción de ticket normal -->
                            <button 
                                type="button"
                                onclick="window.open('{{ route('pos.ticket.imprimir', $ultimaVenta->id) }}', 'TicketTermico', 'width=380,height=650,toolbar=no,menubar=no,location=no,status=no')"
                                style="width: 100%; padding: 10px; background: #059669; color: #ffffff; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                <x-heroicon-o-printer style="width: 16px; height: 16px;" />
                                Imprimir Ticket 80mm
                            </button>
                        @endif

                        <button 
                            type="button"
                            wire:click="$set('showTicketModal', false)"
                            style="width: 100%; padding: 10px; background: #64748b; color: #ffffff; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 12px;">
                            Nueva Orden / Venta
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>