<div class="space-y-6">
    <x-wfm.page-header title="Colas de Atención" description="Administración de las colas de calidad disponibles." />

    <x-wfm.section title="Nueva Cola">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <flux:input wire:model="form.code" label="Código" placeholder="CM-Tr" />
            <flux:input wire:model="form.name" label="Nombre" placeholder="Citas Médicas - Trámite" />
            <flux:button wire:click="createQueue" icon="plus">Agregar Cola</flux:button>
        </div>
    </x-wfm.section>

    <x-wfm.table :headers="['Código', 'Nombre', 'Estado', 'Acciones']" compact>
        @foreach($queues as $queue)
            <flux:table.row :key="$queue->id">
                <flux:table.cell>
                    <flux:badge size="sm" color="slate">{{ $queue->code }}</flux:badge>
                </flux:table.cell>
                <flux:table.cell class="text-sm">{{ $queue->name }}</flux:table.cell>
                <flux:table.cell>
                    <x-wfm.agent-status :status="$queue->is_active ? 'available' : 'offline'" :label="$queue->is_active ? 'Activa' : 'Inactiva'" size="xs" />
                </flux:table.cell>
                <flux:table.cell>
                    <flux:button wire:click="toggleActive('{{ $queue->id }}')" variant="ghost" size="sm" icon="{{ $queue->is_active ? 'pause' : 'play' }}">
                        {{ $queue->is_active ? 'Desactivar' : 'Activar' }}
                    </flux:button>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </x-wfm.table>
</div>
