<div class="space-y-8">
    <flux:card>
        <flux:heading size="xl">Canales</flux:heading>
        <p class="text-sm text-slate-600">Gestiona los canales top-level del Contact Center (relacionados con colas y
            tipos).</p>
    </flux:card>

    <flux:card>
        <form wire:submit.prevent="save" class="grid gap-4 md:grid-cols-3">
            <flux:input wire:model.defer="form.name" label="Nombre" />
            <flux:error name="form.name" />

            <flux:textarea wire:model.defer="form.description" label="Descripción" rows="2" />
            <flux:error name="form.description" />

            <div class="space-y-2">
                <flux:checkbox wire:model.defer="form.is_active" label="Activo" />
                <flux:error name="form.is_active" />
                <flux:button type="submit" variant="primary">
                    {{ $editing ? 'Actualizar canal' : 'Crear canal' }}
                </flux:button>
                @if($editing)
                    <flux:button type="button" variant="secondary" wire:click="resetForm">Cancelar</flux:button>
                @endif
            </div>
        </form>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Canales registrados</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Descripción</flux:table.column>
                <flux:table.column>Activo</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($channels as $channel)
                    <flux:table.row :key="$channel->id">
                        <flux:table.cell>{{ $channel->name }}</flux:table.cell>
                        <flux:table.cell>{{ $channel->description ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$channel->is_active ? 'green' : 'slate'" size="sm">
                                {{ $channel->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button wire:click="edit('{{ $channel->id }}')" size="sm" variant="ghost">Editar
                            </flux:button>
                            <flux:button wire:click="delete('{{ $channel->id }}')" size="sm" variant="danger">Eliminar
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" align="center">No hay canales registrados.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
