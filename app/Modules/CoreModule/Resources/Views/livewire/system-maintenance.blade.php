<section class="max-w-2xl">
    <flux:card>
        <div class="flex items-center gap-4 mb-6">
            <div class="p-3 bg-indigo-500/10 rounded-xl">
                <flux:icon.wrench-screwdriver class="text-indigo-500" />
            </div>
            <div>
                <flux:heading size="xl">Modo Mantenimiento</flux:heading>
                <flux:subheading>Controla el acceso global al sistema Antigravity</flux:subheading>
            </div>
        </div>

        <div class="space-y-6">
            <flux:field>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:label>Estado del Sistema</flux:label>
                        <flux:description>Cuando está activo, solo los administradores pueden navegar.</flux:description>
                    </div>
                    <flux:switch wire:model.live="enabled" wire:change="toggle" />
                </div>
            </flux:field>

            <flux:separator variant="subtle" />

            <flux:input 
                wire:model="message" 
                label="Mensaje para los Usuarios" 
                placeholder="Ej. El sistema regresará en 15 minutos..."
            >
                <x-slot name="description">
                    Este texto aparecerá en la pantalla de bloqueo para los usuarios.
                </x-slot>
            </flux:input>

            <div class="flex justify-end">
                <flux:button wire:click="toggle" variant="primary">
                    Actualizar Mensaje
                </flux:button>
            </div>
        </div>

        @if($enabled)
            <div class="mt-8 p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl flex gap-3 items-start">
                <flux:icon.exclamation-triangle class="text-amber-500 shrink-0" />
                <div class="text-sm text-amber-200/80">
                    <p class="font-semibold text-amber-500">Advertencia</p>
                    El sistema está actualmente inaccesible para todos los agentes y supervisores sin privilegios de administrador.
                </div>
            </div>
        @endif
    </flux:card>
</section>
