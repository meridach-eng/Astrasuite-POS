<x-filament-panels::page>
    <form wire:submit="guardar" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-end gap-3 pt-4">
            <x-filament::button 
                type="button" 
                color="gray" 
                wire:click="cancelar"
            >
                Cancelar
            </x-filament::button>

            <x-filament::button type="submit">
                Guardar Configuración
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>