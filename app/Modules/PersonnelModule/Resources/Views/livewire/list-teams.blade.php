<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Equipos</flux:heading>
            <flux:subheading>Gestiona la estructura de equipos operativos y su sincronización con Cisco.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button wire:click="syncWithCisco" icon="arrow-path" variant="ghost" title="Sincronizar con Cisco"
                wire:loading.attr="disabled">
                <span class="hidden md:inline">Sincronizar</span>
            </flux:button>
            <flux:button href="{{ route('organization.teams.create') }}" variant="primary" icon="plus" wire:navigate>
                Nuevo Equipo
            </flux:button>
        </div>
    </div>

    <flux:card>
        <div class="space-y-4">
            <!-- Filtros -->
            <div class="flex flex-col md:flex-row gap-3">
                <div class="flex-1">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o descripción..."
                        icon="magnifying-glass" clearable />
                </div>
                <div class="w-full md:w-48">
                    <flux:select wire:model.live="activeFilter" placeholder="Todos los estados" clearable>
                        <flux:select.option value="1">Activos</flux:select.option>
                        <flux:select.option value="0">Inactivos</flux:select.option>
                    </flux:select>
                </div>
                <div class="w-full md:w-32">
                    <flux:select wire:model.live="perPage">
                        <flux:select.option value="10">10 por pág.</flux:select.option>
                        <flux:select.option value="25">25 por pág.</flux:select.option>
                        <flux:select.option value="50">50 por pág.</flux:select.option>
                    </flux:select>
                </div>
            </div>

            <!-- Tabla -->
            <flux:table :paginate="$teams" class="overflow-visible">
                <flux:table.columns>
                    <flux:table.column :sortable="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Nombre</flux:table.column>
                    <flux:table.column>Supervisor</flux:table.column>
                    <flux:table.column>Descripción</flux:table.column>
                    <flux:table.column>Miembros</flux:table.column>
                    <flux:table.column :sortable="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">Estado</flux:table.column>
                    <flux:table.column align="end">Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($teams as $team)
                        <flux:table.row :key="$team->id">
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-white">
                                {{ $team->name }}
                            </flux:table.cell>

                            <flux:table.cell>
                                @if($team->supervisor)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar initials="{{ $team->supervisor->initials }}" size="xs" />
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $team->supervisor->full_name }}</span>
                                            <span class="text-xs text-zinc-500">{{ $team->supervisor->employee_number }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400 italic">No asignado</span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell class="text-zinc-500 max-w-xs truncate">
                                {{ $team->description ?: 'Sin descripción' }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge variant="subtle" size="sm" icon="users">
                                    {{ $team->users_count ?? 0 }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if($team->is_active)
                                    <flux:badge color="green" size="sm">Activo</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm">Inactivo</flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex justify-end gap-1">
                                    <flux:button href="{{ route('organization.teams.show', $team) }}" variant="ghost"
                                        size="sm" icon="eye" :inset="true" wire:navigate />
                                    <flux:button href="{{ route('organization.teams.transfer', $team) }}" variant="ghost"
                                        size="sm" icon="arrows-right-left" :inset="true" wire:navigate title="Transferir Miembros" />
                                    <flux:button href="{{ route('organization.teams.edit', $team) }}" variant="ghost"
                                        size="sm" icon="pencil-square" :inset="true" wire:navigate />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-20">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-full">
                                        <flux:icon.user-group class="size-10 text-zinc-300" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-zinc-900 dark:text-white font-medium">No se encontraron equipos</span>
                                        <span class="text-zinc-500 text-sm">Intenta ajustar los filtros de búsqueda.</span>
                                    </div>
                                    @if($search || $activeFilter !== null)
                                        <flux:button wire:click="$set('search', ''); $set('activeFilter', null)" variant="subtle" size="sm" class="mt-2"> Limpiar filtros </flux:button>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>