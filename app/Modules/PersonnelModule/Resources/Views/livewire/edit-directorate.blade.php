<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center space-x-4">
                <flux:link href="{{ route('organization.directorates.show', $directorate) }}" variant="ghost">
                    ← Volver
                </flux:link>
                <h1 class="text-2xl font-bold text-gray-900">Editar Dirección</h1>
            </div>
        </div>

        <form wire:submit="save" class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-6">
                <flux:field>
                    <flux:input wire:model="name" label="Nombre *" placeholder="Ingresa el nombre de la dirección"
                        required />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:textarea wire:model="description" label="Descripción"
                        placeholder="Describe las funciones de la dirección" rows="4" />
                    <flux:error name="description" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="is_active" label="Dirección activa" />
                    <flux:error name="is_active" />
                </flux:field>
            </div>

            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <flux:link href="{{ route('organization.directorates.show', $directorate) }}" variant="ghost">
                    Cancelar
                </flux:link>
                <flux:button type="submit" variant="primary">
                    Actualizar Dirección
                </flux:button>
            </div>
        </form>
    </div>
</div>
