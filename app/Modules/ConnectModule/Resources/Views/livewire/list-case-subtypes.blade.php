<div class="space-y-6">
    <x-wfm.page-header title="Tipos de Consulta" description="Administra los tipos de consulta por cola del Contact Center.">
        <x-slot:filters>
            <x-wfm.filter-bar>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar tipo de consulta..." class="!w-56" />
            </x-wfm.filter-bar>
        </x-slot:filters>
    </x-wfm.page-header>

    <x-wfm.section>
        <form wire:submit.prevent="save" class="grid gap-4 md:grid-cols-3">
            <flux:select wire:model.defer="form.queue_id" label="Cola">
                <flux:select.option value="">Seleccionar</flux:select.option>
                @foreach($queues as $queue)
                    <flux:select.option value="{{ $queue->id }}">{{ $queue->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.defer="form.code" label="Código" />
            <flux:input wire:model.defer="form.name" label="Nombre" />
            <flux:textarea wire:model.defer="form.description" label="Descripción" rows="2" />
            <div class="space-y-2">
                <flux:checkbox wire:model.defer="form.is_active" label="Activo" />
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">
                        {{ $editing ? 'Actualizar tipo' : 'Crear tipo' }}
                    </flux:button>
                    @if($editing)
                        <flux:button type="button" variant="ghost" wire:click="resetForm">Cancelar</flux:button>
                    @endif
                </div>
            </div>
        </form>
    </x-wfm.section>

    <x-wfm.section title="Tipos de Consulta Registrados">
        <x-wfm.table :headers="['Cola', 'Código', 'Nombre', 'Activo', 'Acciones']" :paginate="$subtypes" compact>
            @forelse($subtypes as $subtype)
                <flux:table.row :key="$subtype->id">
                    <flux:table.cell>{{ $subtype->queue->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $subtype->code }}</flux:table.cell>
                    <flux:table.cell>{{ $subtype->name }}</flux:table.cell>
                    <flux:table.cell>
                        <x-wfm.agent-status :status="$subtype->is_active ? 'available' : 'offline'" :label="$subtype->is_active ? 'Activo' : 'Inactivo'" size="xs" />
                    </flux:table.cell>
                    <flux:table.cell class="text-right">
                        <flux:button wire:click="edit({{ $subtype->id }})" size="sm" variant="ghost">Editar</flux:button>
                        <flux:button wire:click="delete({{ $subtype->id }})" wire:confirm="¿Eliminar este tipo de consulta?" size="sm" variant="danger">Eliminar</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <x-wfm.empty icon="tag" message="No hay tipos de consulta registrados." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </x-wfm.table>
    </x-wfm.section>
</div>
