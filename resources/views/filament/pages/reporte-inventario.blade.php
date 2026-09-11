<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- SECCIÓN 1: Tabla nativa de Filament para Stock Bajo -->
        <div style="background: #ffffff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            {{ $this->table }}
        </div>

        <!-- SECCIÓN 2: Tabla limpia de Lotes Próximos a Vencer con Selector de Días -->
        <div style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            
            <!-- Cabecera y Selector -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">Lotes Próximos a Vencer o Vencidos</h3>
                    <p style="font-size: 12px; color: #64748b; margin: 0;">Lotes activos con stock remanente que caducarán en el periodo seleccionado o ya expiraron.</p>
                </div>

                <!-- Selector Reactivo de Días -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="diasVencimiento" style="font-size: 13px; font-weight: 600; color: #475569;">Ver próximos:</label>
                    <select wire:model.live="diasVencimiento" id="diasVencimiento" style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; font-size: 13px; background: #f8fafc; color: #0f172a; outline: none; cursor: pointer;">
                        <option value="30">30 días</option>
                        <option value="60">60 días</option>
                        <option value="90">90 días</option>
                    </select>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 700;">
                            @if(! $sucursalId)
                                <th style="padding: 10px 12px;">Sucursal</th>
                            @endif
                            <th style="padding: 10px 12px;">No. Lote</th>
                            <th style="padding: 10px 12px;">Producto</th>
                            <th style="padding: 10px 12px; text-align: right;">Cantidad en Lote</th>
                            <th style="padding: 10px 12px; text-align: center;">Vencimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->lotesProximosVencer as $lote)
                            @php
                                $esVencido = optional($lote->fecha_vencimiento)->isPast();
                            @endphp
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                @if(! $sucursalId)
                                    <td style="padding: 10px 12px;">
                                        <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                            {{ $lote->sucursal->nombre ?? 'N/A' }}
                                        </span>
                                    </td>
                                @endif
                                <td style="padding: 10px 12px; font-weight: 700; color: #334155;">
                                    <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 11px;">
                                        {{ $lote->numero_lote }}
                                    </span>
                                </td>
                                <td style="padding: 10px 12px; font-weight: 600; color: #0f172a;">
                                    {{ $lote->producto->nombre ?? 'N/A' }}
                                </td>
                                <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #0f172a;">
                                    {{ number_format($lote->cantidad_actual, 2) }}
                                </td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; background: {{ $esVencido ? '#fee2e2' : '#fef3c7' }}; color: {{ $esVencido ? '#b91c1c' : '#b45309' }};">
                                        {{ optional($lote->fecha_vencimiento)->format('d/m/Y') }} — {{ $esVencido ? '¡VENCIDO!' : 'Próximo a vencer' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: 30px; text-align: center; color: #94a3b8; font-size: 13px;">
                                    No hay lotes próximos a vencer en los siguientes {{ $diasVencimiento }} días.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>