<x-filament-panels::page>
    @if(session('sucursal_activa_id'))
        <div class="p-4 mb-4 rounded-xl border border-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 flex items-center justify-between">
            <div>
                <span class="font-bold">Sucursal Activa Actual:</span> {{ session('sucursal_activa_nombre') }}
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 bg-emerald-200 dark:bg-emerald-900 rounded-lg">En Operación</span>
        </div>
    @else
        <div class="p-4 mb-4 rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300">
            <span class="font-bold">Atención:</span> Selecciona la sucursal en la que vas a trabajar hoy para habilitar tu caja y registrar ventas.
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($this->sucursales as $sucursal)
            <div class="flex flex-col justify-between p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 hover:shadow-md transition">
                <div>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-primary-100 dark:bg-primary-950/50 rounded-xl text-primary-600">
                            <x-heroicon-o-building-storefront class="w-8 h-8" />
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white">
                                {{ $sucursal->nombre }}
                            </h3>
                            <span class="text-xs font-medium text-gray-500">
                                Código SAT: {{ $sucursal->codigo_establecimiento_sat }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400 border-t border-gray-100 dark:border-gray-800 pt-3">
                        @if($sucursal->telefono)
                            <p class="flex items-center gap-2">
                                <x-heroicon-m-phone class="w-4 h-4 text-gray-400" />
                                {{ $sucursal->telefono }}
                            </p>
                        @endif
                        @if($sucursal->direccion)
                            <p class="flex items-center gap-2">
                                <x-heroicon-m-map-pin class="w-4 h-4 text-gray-400" />
                                {{ $sucursal->direccion }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="mt-6">
                    <button 
                        type="button"
                        wire:click="ingresarSucursal({{ $sucursal->id }})"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl transition shadow-sm"
                    >
                        <x-heroicon-m-arrow-right-on-rectangle class="w-5 h-5" />
                        Ingresar a Sucursal
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500">
                No tienes sucursales asignadas. Contacta al administrador.
            </div>
        @endforelse
    </div>
</x-filament-panels::page>