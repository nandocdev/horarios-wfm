<div class="space-y-6">
    <div>
        <flux:heading size="xl">Tipos de Consulta</flux:heading>
        <flux:subheading>Administra los tipos de consulta por cola del Contact Center.</flux:subheading>
    </div>

    <flux:card>
        <form wire:submit="save" class="grid gap-4 md:grid-cols-3">
            <flux:field>
                <flux:label>Cola</flux:label>
                <flux:select wire:model="queue_id" placeholder="Seleccionar">
                    @foreach($queues as $queue)
                        <flux:select.option value="{{ $queue->id }}">{{ $queue->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="queue_id" />
            </flux:field>

            <flux:field>
                <flux:label>Código</flux:label>
                <flux:input wire:model="code" placeholder="Ej. SOP-001" />
                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label>Nombre</flux:label>
                <flux:input wire:model="name" placeholder="Ej. Soporte Técnico N1" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Descripción</flux:label>
                <flux:textarea wire:model="description" rows="2" />
                <flux:error name="description" />
            </flux:field>

            <div class="space-y-2">
                <flux:checkbox wire:model="is_active" label="Activo" />
                <flux:error name="is_active" />
                <flux:button type="submit" variant="primary">
                    {{ $editing ? 'Actualizar tipo' : 'Crear tipo' }}
                </flux:button>
                @if($editing)
                    <flux:button type="button" variant="secondary" wire:click="resetForm">Cancelar</flux:button>
                @endif
            </div>
        </form>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Tipos de consulta registrados</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Cola</flux:table.column>
                <flux:table.column>Código</flux:table.column>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Activo</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($subtypes as $subtype)
                    <flux:table.row :key="$subtype->id">
                        <flux:table.cell>{{ $subtype->queue->name ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $subtype->code }}</flux:table.cell>
                        <flux:table.cell>{{ $subtype->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$subtype->is_active ? 'green' : 'slate'" size="sm">
                                {{ $subtype->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button wire:click="edit({{ $subtype->id }})" size="sm" variant="ghost">Editar</flux:button>
                            <flux:button wire:click="delete({{ $subtype->id }})" size="sm" variant="danger">Eliminar</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" align="center">No hay tipos de consulta registrados.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
