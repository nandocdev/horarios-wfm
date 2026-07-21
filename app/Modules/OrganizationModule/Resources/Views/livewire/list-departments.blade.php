<div class="space-y-6">
    <x-wfm.page-header title="Departamentos" description="Gestiona los departamentos por dirección.">
        <x-slot:actions>
            <flux:button href="{{ route('organization.departments.create') }}" variant="primary" icon="plus" wire:navigate>Nuevo Departamento</flux:button>
        </x-slot:actions>
        <x-slot:filters>
            <x-wfm.filter-bar>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o descripción..." class="!w-56" />
                <flux:select wire:model.live="directorateFilter" placeholder="Todas las direcciones" class="!w-44">
                    @foreach($this->directorates as $directorate)
                        <flux:select.option value="{{ $directorate->id }}">{{ $directorate->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="perPage" class="!w-28">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                </flux:select>
            </x-wfm.filter-bar>
        </x-slot:filters>
    </x-wfm.page-header>

    <x-wfm.section title="Departamentos Registrados">
        <x-wfm.table :headers="['Nombre', 'Dirección', 'Descripción', 'Acciones']" :paginate="$departments" compact>
            @forelse($departments as $department)
                <flux:table.row :key="$department->id">
                    <flux:table.cell class="font-medium">{{ $department->name }}</flux:table.cell>
                    <flux:table.cell>{{ $department->directorate->name }}</flux:table.cell>
                    <flux:table.cell class="text-xs text-wfm-surface-muted max-w-xs truncate">{{ $department->description ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-right">
                        <flux:button href="{{ route('organization.departments.show', $department) }}" variant="ghost" size="sm" icon="eye" wire:navigate />
                        <flux:button href="{{ route('organization.departments.edit', $department) }}" variant="ghost" size="sm" icon="pencil-square" wire:navigate />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        <x-wfm.empty icon="building-office-2" message="No se encontraron departamentos." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </x-wfm.table>
    </x-wfm.section>
</div>
