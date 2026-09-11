<div class="w-full">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; width: 100%;">
        {{-- Ventas (Índigo Astra) --}}
        <div style="background: #ffffff; border-radius: 16px; padding: 22px; display: flex; align-items: center; gap: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <x-heroicon-o-shopping-cart style="width: 24px; height: 24px;" />
            </div>
            <div>
                <div style="font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.1;">{{ number_format($ventas) }}</div>
                <span style="font-size: 13px; font-weight: 500; color: #64748b;">Ventas</span>
            </div>
        </div>

        {{-- Clientes (Púrpura / Violeta) --}}
        <div style="background: #ffffff; border-radius: 16px; padding: 22px; display: flex; align-items: center; gap: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <x-heroicon-o-users style="width: 24px; height: 24px;" />
            </div>
            <div>
                <div style="font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.1;">{{ number_format($clientes) }}</div>
                <span style="font-size: 13px; font-weight: 500; color: #64748b;">Clientes</span>
            </div>
        </div>

        {{-- Productos (Ámbar Dorado) --}}
        <div style="background: #ffffff; border-radius: 16px; padding: 22px; display: flex; align-items: center; gap: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: #fffbeb; color: #d97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <x-heroicon-o-cube style="width: 24px; height: 24px;" />
            </div>
            <div>
                <div style="font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.1;">{{ number_format($productos) }}</div>
                <span style="font-size: 13px; font-weight: 500; color: #64748b;">Productos</span>
            </div>
        </div>

        {{-- Ingresos (Verde Esmeralda) --}}
        <div style="background: #ffffff; border-radius: 16px; padding: 22px; display: flex; align-items: center; gap: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <x-heroicon-o-currency-dollar style="width: 24px; height: 24px;" />
            </div>
            <div>
                <div style="font-size: 26px; font-weight: 800; color: #059669; line-height: 1.1;">Q{{ number_format($ingresos, 2) }}</div>
                <span style="font-size: 13px; font-weight: 500; color: #64748b;">Ingresos</span>
            </div>
        </div>
    </div>
</div>