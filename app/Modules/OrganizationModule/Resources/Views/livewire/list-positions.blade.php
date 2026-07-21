<div class="space-y-6">
    <x-wfm.page-header title="Posiciones" description="Gestiona los cargos de la organización.">
        <x-slot:actions>
            <flux:button href="{{ route('organization.positions.create') }}" variant="primary" icon="plus" wire:navigate>Nueva Posición</flux:button>
        </x-slot:actions>
        <x-slot:filters>
            <x-wfm.filter-bar>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o descripción..." class="!w-56" />
                <flux:select wire:model.live="departmentFilter" placeholder="Todos los departamentos" class="!w-44">
                    @foreach($this->departments as $department)
                        <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="activeFilter" placeholder="Todos los estados" class="!w-36">
                    <flux:select.option value="1">Activas</flux:select.option>
                    <flux:select.option value="0">Inactivas</flux:select.option>
                </flux:select>
                <flux:select wire:model.live="perPage" class="!w-28">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                </flux:select>
            </x-wfm.filter-bar>
        </x-slot:filters>
    </x-wfm.page-header>

    <x-wfm.section title="Posiciones Registradas">
        <x-wfm.table :headers="['Nombre', 'Código', 'Departamento', 'Estado', 'Acciones']" :paginate="$positions" compact>
            @forelse($positions as $position)
                <flux:table.row :key="$position->id">
                    <flux:table.cell class="font-medium">{{ $position->name }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $position->position_code }}</flux:table.cell>
                    <flux:table.cell>{{ $position->department->name }}</flux:table.cell>
                    <flux:table.cell>
                        <x-wfm.agent-status :status="$position->is_active ? 'available' : 'offline'" :label="$position->is_active ? 'Activa' : 'Inactiva'" size="xs" />
                    </flux:table.cell>
                    <flux:table.cell class="text-right">
                        <flux:button href="{{ route('organization.positions.show', $position) }}" variant="ghost" size="sm" icon="eye" wire:navigate />
                        <flux:button href="{{ route('organization.positions.edit', $position) }}" variant="ghost" size="sm" icon="pencil-square" wire:navigate />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <x-wfm.empty icon="briefcase" message="No se encontraron posiciones." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </x-wfm.table>
    </x-wfm.section>
</div>
