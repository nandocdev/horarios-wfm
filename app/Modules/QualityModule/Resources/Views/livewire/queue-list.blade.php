<div class="space-y-8">
    <div>
        <flux:heading size="xl" level="1">Colas de Atención</flux:heading>
        <flux:subheading>Administración de las colas de calidad disponibles</flux:subheading>
    </div>

    <flux:card>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <flux:input wire:model="code" label="Código" placeholder="CM-Tr" maxlength="20" />
                <flux:input wire:model="name" label="Nombre" placeholder="Citas Médicas - Trámite" />
                <flux:button wire:click="createQueue" icon="plus" class="self-end">Agregar Cola</flux:button>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Código</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Nombre</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Estado</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($queues as $queue)
                    <flux:table.row :key="$queue->id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <flux:table.cell class="py-2">
                            <flux:badge size="sm" color="slate" inset="top">{{ $queue->code }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="py-2 text-sm">{{ $queue->name }}</flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:badge size="sm" color="{{ $queue->is_active ? 'green' : 'zinc' }}" inset="top">
                                {{ $queue->is_active ? 'Activa' : 'Inactiva' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:button wire:click="toggleActive('{{ $queue->id }}')" variant="ghost" size="sm" icon="{{ $queue->is_active ? 'pause' : 'play' }}">
                                {{ $queue->is_active ? 'Desactivar' : 'Activar' }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
