<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Panel de Reconocimientos</flux:heading>
            <flux:subheading>Gestiona los reconocimientos públicos entre colaboradores.</flux:subheading>
        </div>
        <flux:button href="{{ route('communications.shoutouts.create') }}" variant="primary" icon="plus" wire:navigate>
            Nuevo Reconocimiento
        </flux:button>
    </div>

    <flux:card>
        <div class="mb-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar reconocimiento..." icon="magnifying-glass" />
        </div>

        <flux:table :paginate="$shoutouts">
            <flux:table.columns>
                <flux:table.column>ID</flux:table.column>
                <flux:table.column>Empleado</flux:table.column>
                <flux:table.column>Contenido</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($shoutouts as $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell>{{ $item->id }}</flux:table.cell>
                        <flux:table.cell>{{ $item->employee->full_name ?? 'N/A' }}</flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate">{{ $item->message }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-col gap-1">
                                @php
                                    $statusColor = match($item->status) {
                                        'draft' => 'slate',
                                        'pending_review' => 'orange',
                                        'published' => 'green',
                                        'archived' => 'red',
                                        default => 'slate',
                                    };
                                    $statusLabel = match($item->status) {
                                        'draft' => 'Borrador',
                                        'pending_review' => 'En Revisión',
                                        'published' => 'Publicado',
                                        'archived' => 'Archivado',
                                        default => $item->status,
                                    };
                                @endphp
                                <flux:badge :color="$statusColor" size="sm" variant="subtle">
                                    {{ $statusLabel }}
                                </flux:badge>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                @if($item->canBeEdited())
                                    <flux:button href="{{ route('communications.shoutouts.edit', $item) }}" 
                                        variant="ghost" icon="pencil-square" size="sm" wire:navigate />
                                @endif

                                <flux:button wire:click="deleteShoutout({{ $item->id }})" 
                                    wire:confirm="¿Estás seguro de eliminar este reconocimiento?"
                                    variant="ghost" color="red" icon="trash" size="sm" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" align="center" class="py-12">
                            <flux:text>No hay reconocimientos registrados.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $shoutouts->links() }}
        </div>
    </flux:card>
</div>
