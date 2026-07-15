<div class="space-y-8">
    <flux:card class="">
        <flux:heading size="xl">Canales</flux:heading>
        <p class="text-sm text-slate-600">Gestiona los canales top-level del Contact Center (relacionados con colas y
            tipos).</p>
    </flux:card>

    <flux:card class="">
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
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Nombre</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Descripción
                </flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Activo</flux:table.column>
                <flux:table.column align="end" class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Acciones
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($channels as $channel)
                    <flux:table.row :key="$channel->id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 py-2">
                        <flux:table.cell class="py-2">{{ $channel->name }}</flux:table.cell>
                        <flux:table.cell class="py-2">{{ $channel->description ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:badge :color="$channel->is_active ? 'green' : 'slate'" size="sm">
                                {{ $channel->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="py-2">
                            <flux:button wire:click="edit('{{ $channel->id }}')" size="sm" variant="ghost">Editar
                            </flux:button>
                            <flux:button wire:click="delete('{{ $channel->id }}')" size="sm" variant="danger">Eliminar
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell class="py-2" colspan="4" align="center">No hay canales registrados.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>