<div class="space-y-6">
    <flux:card>
        <flux:heading size="xl">Tipos de consulta</flux:heading>
        <p class="text-sm text-slate-600">Administra los tipos de consulta por cola del Contact Center.</p>
    </flux:card>

    <flux:card>
        <form wire:submit.prevent="save" class="grid gap-4 md:grid-cols-3">
            <flux:select wire:model.defer="form.queue_id" label="Cola">
                <flux:select.option value="">Seleccionar</flux:select.option>
                @foreach($queues as $queue)
                    <flux:select.option value="{{ $queue->id }}">{{ $queue->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="form.queue_id" />

            <flux:input wire:model.defer="form.code" label="Código" />
            <flux:error name="form.code" />

            <flux:input wire:model.defer="form.name" label="Nombre" />
            <flux:error name="form.name" />

            <flux:textarea wire:model.defer="form.description" label="Descripción" rows="2" />
            <flux:error name="form.description" />

            <div class="space-y-2">
                <flux:checkbox wire:model.defer="form.is_active" label="Activo" />
                <flux:error name="form.is_active" />
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
                            <flux:button wire:click="edit({{ $subtype->id }})" size="sm" variant="ghost">Editar
                            </flux:button>
                            <flux:button wire:click="delete({{ $subtype->id }})"
                                wire:confirm="¿Eliminar este tipo de consulta?" size="sm" variant="danger">Eliminar
                            </flux:button>
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
