<div class="space-y-6">
    <x-wfm.page-header title="Direcciones" description="Gestiona las direcciones de la organización.">
        <x-slot:actions>
            <flux:button href="{{ route('organization.directorates.create') }}" variant="primary" icon="plus" wire:navigate>Nueva Dirección</flux:button>
        </x-slot:actions>
        <x-slot:filters>
            <x-wfm.filter-bar>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o descripción..." class="!w-56" />
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

    <x-wfm.section title="Direcciones Registradas">
        <x-wfm.table :headers="['Nombre', 'Descripción', 'Estado', 'Departamentos', 'Acciones']" :paginate="$directorates" compact>
            @forelse($directorates as $directorate)
                <flux:table.row :key="$directorate->id">
                    <flux:table.cell class="font-medium">{{ $directorate->name }}</flux:table.cell>
                    <flux:table.cell class="text-xs text-wfm-surface-muted max-w-xs truncate">{{ $directorate->description ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <x-wfm.agent-status :status="$directorate->is_active ? 'available' : 'offline'" :label="$directorate->is_active ? 'Activa' : 'Inactiva'" size="xs" />
                    </flux:table.cell>
                    <flux:table.cell>{{ $directorate->departments_count ?? 0 }}</flux:table.cell>
                    <flux:table.cell class="text-right">
                        <flux:button href="{{ route('organization.directorates.show', $directorate) }}" variant="ghost" size="sm" icon="eye" wire:navigate />
                        <flux:button href="{{ route('organization.directorates.edit', $directorate) }}" variant="ghost" size="sm" icon="pencil-square" wire:navigate />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <x-wfm.empty icon="building-library" message="No se encontraron direcciones." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </x-wfm.table>
    </x-wfm.section>
</div>
