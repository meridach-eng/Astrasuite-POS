<div class="fi-wi-detalles-custom w-full" style="width: 100%;">
    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; width: 100%; align-items: stretch;">
        {{-- Columna 1: Resumen financiero --}}
        <div style="background: #ffffff; border-radius: 16px; padding: 24px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="font-size: 17px; font-weight: 700; color: #0f172a; margin-bottom: 20px;">Resumen financiero</h3>
                
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    {{-- Utilidad neta (Índigo) --}}
                    <div style="display: flex; align-items: flex-start; gap: 14px;">
                        <div style="width: 42px; height: 42px; border-radius: 12px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <x-heroicon-o-chart-bar style="width: 22px; height: 22px;" />
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 14px; font-weight: 600; color: #0f172a;">Utilidad neta</div>
                            <div style="font-size: 12px; color: #94a3b8;">{{ $conteoVentas }} Ventas</div>
                            <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 2px;">Q{{ number_format($utilidad, 2) }}</div>
                        </div>
                    </div>

                    {{-- Ingresos totales (Esmeralda) --}}
                    <div style="display: flex; align-items: flex-start; gap: 14px;">
                        <div style="width: 42px; height: 42px; border-radius: 12px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <x-heroicon-o-currency-dollar style="width: 22px; height: 22px;" />
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 14px; font-weight: 600; color: #0f172a;">Ingresos totales</div>
                            <div style="font-size: 12px; color: #94a3b8;">Total recaudado</div>
                            <div style="font-size: 16px; font-weight: 700; color: #059669; margin-top: 2px;">Q{{ number_format($ingresos, 2) }}</div>
                        </div>
                    </div>

                    {{-- Gastos totales (Gris Pizarra / Slate) --}}
                    <div style="display: flex; align-items: flex-start; gap: 14px;">
                        <div style="width: 42px; height: 42px; border-radius: 12px; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <x-heroicon-o-credit-card style="width: 22px; height: 22px;" />
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 14px; font-weight: 600; color: #0f172a;">Gastos totales</div>
                            <div style="font-size: 12px; color: #94a3b8;">Gastos y compras</div>
                            <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 2px;">Q{{ number_format($gastos, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Barras semanales en gama Índigo / Violeta --}}
            <div style="padding-top: 20px; margin-top: 20px; border-top: 1px solid #f1f5f9; display: flex; align-items: flex-end; justify-content: space-between; height: 95px; gap: 8px;">
                @foreach([35, 50, 65, 45, 95, 75, 80] as $index => $altura)
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: flex-end; gap: 6px;">
                        <div style="width: 100%; border-radius: 6px; background: {{ $index === 4 ? '#4f46e5' : '#e0e7ff' }}; height: {{ $altura }}%;"></div>
                        <span style="font-size: 10px; color: #94a3b8; font-weight: 600;">{{ ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'][$index] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Columna 2: Productos destacados (Azul Océano / Zafiro) --}}
        <div style="background: #ffffff; border-radius: 16px; padding: 24px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <h3 style="font-size: 17px; font-weight: 700; color: #0f172a; margin-bottom: 20px;">Productos destacados</h3>
            
            <div style="display: flex; flex-direction: column; gap: 14px;">
                @forelse($productosPopulares as $producto)
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                            <div style="width: 44px; height: 44px; border-radius: 12px; background: #f0f9ff; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <x-heroicon-o-cube style="width: 22px; height: 22px;" />
                            </div>
                            <div style="min-width: 0;">
                                <div style="font-size: 14px; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $producto->nombre }}</div>
                                <div style="font-size: 12px; color: #94a3b8;">Código: {{ $producto->codigo_barras ?? $producto->id }}</div>
                            </div>
                        </div>
                        <div style="font-size: 14px; font-weight: 700; color: #0284c7; flex-shrink: 0;">
                            Q{{ number_format($producto->precio_venta, 2) }}
                        </div>
                    </div>
                @empty
                    <div style="font-size: 13px; color: #94a3b8; text-align: center; padding: 20px 0;">No hay productos registrados</div>
                @endforelse
            </div>
        </div>

        {{-- Columna 3: Últimas transacciones --}}
        <div style="background: #ffffff; border-radius: 16px; padding: 24px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <h3 style="font-size: 17px; font-weight: 700; color: #0f172a; margin-bottom: 20px;">Últimas transacciones</h3>
            
            <div style="display: flex; flex-direction: column; gap: 14px;">
                @forelse($transacciones as $transaccion)
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 44px; height: 44px; border-radius: 12px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <x-heroicon-o-banknotes style="width: 22px; height: 22px;" />
                            </div>
                            <div>
                                <div style="font-size: 14px; font-weight: 600; color: #0f172a;">Venta realizada</div>
                                <div style="font-size: 12px; color: #94a3b8;">Comprobante #{{ str_pad($transaccion->id, 5, '0', STR_PAD_LEFT) }}</div>
                            </div>
                        </div>
                        <div style="font-size: 14px; font-weight: 700; color: #059669; flex-shrink: 0;">
                            +Q{{ number_format($transaccion->total, 2) }}
                        </div>
                    </div>
                @empty
                    <div style="font-size: 13px; color: #94a3b8; text-align: center; padding: 20px 0;">No hay transacciones recientes</div>
                @endforelse
            </div>
        </div>
    </div>
</div>