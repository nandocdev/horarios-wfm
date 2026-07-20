<div class="space-y-6">
    <x-wfm.page-header title="Canales" description="Gestiona los canales top-level del Contact Center." />

    <x-wfm.section>
        <form wire:submit.prevent="save" class="grid gap-4 md:grid-cols-3">
            <flux:input wire:model.defer="form.name" label="Nombre" />
            <flux:textarea wire:model.defer="form.description" label="Descripción" rows="2" />
            <div class="space-y-2">
                <flux:checkbox wire:model.defer="form.is_active" label="Activo" />
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">
                        {{ $editing ? 'Actualizar canal' : 'Crear canal' }}
                    </flux:button>
                    @if($editing)
                        <flux:button type="button" variant="ghost" wire:click="resetForm">Cancelar</flux:button>
                    @endif
                </div>
            </div>
        </form>
    </x-wfm.section>

    <x-wfm.section title="Canales Registrados">
        <x-wfm.table :headers="['Nombre', 'Descripción', 'Activo', 'Acciones']" compact>
            @forelse($channels as $channel)
                <flux:table.row :key="$channel->id">
                    <flux:table.cell class="font-medium">{{ $channel->name }}</flux:table.cell>
                    <flux:table.cell class="text-xs text-wfm-surface-muted">{{ $channel->description ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <x-wfm.agent-status :status="$channel->is_active ? 'available' : 'offline'" :label="$channel->is_active ? 'Activo' : 'Inactivo'" size="xs" />
                    </flux:table.cell>
                    <flux:table.cell class="text-right">
                        <flux:button wire:click="edit('{{ $channel->id }}')" size="sm" variant="ghost">Editar</flux:button>
                        <flux:button wire:click="delete('{{ $channel->id }}')" size="sm" variant="danger">Eliminar</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        <x-wfm.empty icon="signal" message="No hay canales registrados." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </x-wfm.table>
    </x-wfm.section>
</div>
