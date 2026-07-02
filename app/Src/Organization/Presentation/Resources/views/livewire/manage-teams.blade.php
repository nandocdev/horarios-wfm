<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Gestión de Equipos</flux:heading>
            <flux:subheading>Administra los equipos de trabajo y su estructura organizacional.</flux:subheading>
        </div>
    </div>

    <flux:card class="space-y-6">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar equipo por nombre..."
            class="max-w-md" icon="magnifying-glass" clearable />

        <flux:table :paginate="$teams">
            <flux:table.columns>
                <flux:table.column>Equipo</flux:table.column>
                <flux:table.column>Supervisor</flux:table.column>
                <flux:table.column>Miembros</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($teams as $team)
                    <flux:table.row :key="$team->id">
                        <flux:table.cell variant="strong">{{ $team->name }}</flux:table.cell>
                        <flux:table.cell>
                            {{ $team->supervisor_id ? "ID: {$team->supervisor_id}" : 'Sin asignar' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm">{{ $team->members->where('is_active', true)->count() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :variant="$team->is_active ? 'success' : 'danger'" size="sm">
                                {{ $team->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center py-10">
                            <flux:text size="sm" class="text-zinc-500 italic">No se encontraron equipos.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
