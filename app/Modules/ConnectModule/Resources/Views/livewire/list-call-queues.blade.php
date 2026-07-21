<div class="space-y-6">
    <x-wfm.page-header title="Catálogo de Colas" description="Gestiona las colas que pueden asignarse a registros de llamada.">
        <x-slot:filters>
            <x-wfm.filter-bar>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar cola..." class="!w-56" />
            </x-wfm.filter-bar>
        </x-slot:filters>
    </x-wfm.page-header>

    <x-wfm.section>
        <form wire:submit.prevent="save" class="grid gap-4 md:grid-cols-3">
            <flux:input wire:model.defer="form.name" label="Nombre de la cola" />
            <flux:textarea wire:model.defer="form.description" label="Descripción" rows="2" />
            <div class="space-y-2">
                <flux:checkbox wire:model.defer="form.is_active" label="Activa" />
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">
                        {{ $editing ? 'Actualizar cola' : 'Crear cola' }}
                    </flux:button>
                    @if($editing)
                        <flux:button type="button" variant="ghost" wire:click="resetForm">Cancelar</flux:button>
                    @endif
                </div>
            </div>
        </form>
    </x-wfm.section>

    <x-wfm.section title="Colas Registradas">
        <x-wfm.table :headers="['Nombre', 'Descripción', 'Tipos asociados', 'Activo', 'Acciones']" :paginate="$queues" compact>
            @forelse($queues as $queue)
                <flux:table.row :key="$queue->id">
                    <flux:table.cell class="font-medium">{{ $queue->name }}</flux:table.cell>
                    <flux:table.cell class="text-xs text-wfm-surface-muted">{{ $queue->description ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $queue->subtypes_count }}</flux:table.cell>
                    <flux:table.cell>
                        <x-wfm.agent-status :status="$queue->is_active ? 'available' : 'offline'" :label="$queue->is_active ? 'Activo' : 'Inactivo'" size="xs" />
                    </flux:table.cell>
                    <flux:table.cell class="text-right">
                        <flux:button wire:click="edit({{ $queue->id }})" size="sm" variant="ghost">Editar</flux:button>
                        <flux:button wire:click="delete({{ $queue->id }})" size="sm" variant="danger">Eliminar</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <x-wfm.empty icon="queue-list" message="No hay colas registradas." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </x-wfm.table>
    </x-wfm.section>
</div>
