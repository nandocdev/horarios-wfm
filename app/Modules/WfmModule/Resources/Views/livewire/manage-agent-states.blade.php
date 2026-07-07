<div class="p-4">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Estados de Agente (Cisco Sync)</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nuevo Estado</flux:button>
    </div>

    <flux:table :paginate="$states">
        <flux:table.columns>
            <flux:table.column>Código Externo</flux:table.column>
            <flux:table.column>Nombre Visual</flux:table.column>
            <flux:table.column>Productividad</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($states as $state)
                <flux:table.row :key="$state->id">
                    <flux:table.cell class="font-mono text-xs">{{ $state->external_code }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $state->color_hex }}"></div>
                            {{ $state->display_name }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$state->is_productive ? 'blue' : 'zinc'">{{ $state->is_productive ? 'Productivo' : 'No Prod.' }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button wire:click="edit({{ $state->id }})" variant="ghost" size="sm" icon="pencil" />
                        <flux:button wire:click="delete({{ $state->id }})" variant="ghost" size="sm" icon="trash" color="red" wire:confirm="¿Estás seguro?" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showModal" class="w-full max-w-sm">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $form->agentState ? 'Editar Estado' : 'Nuevo Estado' }}</flux:heading>

            <flux:input wire:model="form.external_code" label="Código Externo (API)" placeholder="Ej. 1, 2, 5" />
            <flux:input wire:model="form.display_name" label="Nombre a Mostrar" placeholder="Ej. Disponible" />
            
            <flux:input wire:model="form.color_hex" type="color" label="Color en Monitor" />

            <div class="space-y-3">
                <flux:checkbox wire:model="form.is_productive" label="¿Se considera tiempo productivo?" />
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
