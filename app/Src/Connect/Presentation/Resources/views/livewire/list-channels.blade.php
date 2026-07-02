<div class="space-y-6">
    <div>
        <flux:heading size="xl">Canales</flux:heading>
        <flux:subheading>Gestiona los canales top-level del Contact Center.</flux:subheading>
    </div>

    <flux:card>
        <form wire:submit="save" class="grid gap-4 md:grid-cols-3">
            <flux:field>
                <flux:label>Nombre</flux:label>
                <flux:input wire:model="name" placeholder="Ej. Voz, Chat, Email" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Tipo</flux:label>
                <flux:input wire:model="type" placeholder="Ej. voice, chat, email" />
                <flux:error name="type" />
            </flux:field>

            <div class="space-y-2">
                <flux:checkbox wire:model="is_active" label="Activo" />
                <flux:error name="is_active" />
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
                <flux:table.column>ID</flux:table.column>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Activo</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($channels as $channel)
                    <flux:table.row :key="$channel->id">
                        <flux:table.cell>{{ $channel->id }}</flux:table.cell>
                        <flux:table.cell>{{ $channel->name }}</flux:table.cell>
                        <flux:table.cell>{{ $channel->type ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$channel->is_active ? 'green' : 'slate'" size="sm">
                                {{ $channel->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button wire:click="edit('{{ $channel->id }}')" size="sm" variant="ghost">Editar</flux:button>
                            <flux:button wire:click="delete('{{ $channel->id }}')" size="sm" variant="danger">Eliminar</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" align="center">No hay canales registrados.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
