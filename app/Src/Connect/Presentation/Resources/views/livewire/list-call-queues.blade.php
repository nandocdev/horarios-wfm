<div class="space-y-6">
    <div>
        <flux:heading size="xl">Colas de Llamadas</flux:heading>
        <flux:subheading>Gestiona las colas que pueden asignarse a registros de llamada.</flux:subheading>
    </div>

    <flux:card>
        <form wire:submit="save" class="grid gap-4 md:grid-cols-3">
            <flux:field>
                <flux:label>Nombre de la cola</flux:label>
                <flux:input wire:model="name" placeholder="Ej. Soporte Técnico" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Descripción</flux:label>
                <flux:textarea wire:model="description" rows="2" />
                <flux:error name="description" />
            </flux:field>

            <div class="space-y-2">
                <flux:checkbox wire:model="is_active" label="Activa" />
                <flux:error name="is_active" />
                <flux:button type="submit" variant="primary">
                    {{ $editing ? 'Actualizar cola' : 'Crear cola' }}
                </flux:button>
                @if($editing)
                    <flux:button type="button" variant="secondary" wire:click="resetForm">Cancelar</flux:button>
                @endif
            </div>
        </form>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Colas registradas</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Descripción</flux:table.column>
                <flux:table.column>Activo</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($queues as $queue)
                    <flux:table.row :key="$queue->id">
                        <flux:table.cell>{{ $queue->name }}</flux:table.cell>
                        <flux:table.cell>{{ $queue->description ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$queue->is_active ? 'green' : 'slate'" size="sm">
                                {{ $queue->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button wire:click="edit({{ $queue->id }})" size="sm" variant="ghost">Editar</flux:button>
                            <flux:button wire:click="delete({{ $queue->id }})" size="sm" variant="danger">Eliminar</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" align="center">No hay colas registradas.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
