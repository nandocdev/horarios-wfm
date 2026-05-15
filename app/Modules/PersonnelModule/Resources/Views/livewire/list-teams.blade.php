<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Equipos</flux:heading>
            <flux:subheading>Gestiona la estructura de equipos operativos y su sincronización con Cisco.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button wire:click="syncWithCisco" icon="arrow-path" variant="ghost" title="Sincronizar con Cisco"
                wire:loading.attr="disabled" />
            <flux:button href="{{ route('organization.teams.create') }}" variant="primary" icon="plus">
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
                        icon="magnifying-glass" />
                </div>
                <div class="w-full md:w-48">
                    <flux:select wire:model.live="activeFilter" placeholder="Todos los estados">
                        <flux:select.option value="1">Activos</flux:select.option>
                        <flux:select.option value="0">Inactivos</flux:select.option>
                    </flux:select>
                </div>
                <div class="w-full md:w-32">
                    <flux:select wire:model.live="perPage">
                        <flux:select.option value="10">10</flux:select.option>
                        <flux:select.option value="25">25</flux:select.option>
                        <flux:select.option value="50">50</flux:select.option>
                    </flux:select>
                </div>
            </div>

            <!-- Tabla -->
            <flux:table :paginate="$teams">
                <flux:table.columns>
                    <flux:table.column sortable>Nombre</flux:table.column>
                    <flux:table.column>Supervisor</flux:table.column>
                    <flux:table.column>Descripción</flux:table.column>
                    <flux:table.column>Miembros</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
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
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $team->supervisor->full_name }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400 italic">No asignado</span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell class="text-zinc-500 max-w-xs truncate">
                                {{ $team->description ?: 'Sin descripción' }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge variant="ghost" size="sm" icon="users">
                                    {{ $team->users_count ?? 0 }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if($team->is_active)
                                    <flux:badge color="green">Activo</flux:badge>
                                @else
                                    <flux:badge color="red">Inactivo</flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex justify-end gap-1">
                                    <flux:button href="{{ route('organization.teams.show', $team) }}" variant="ghost"
                                        size="sm" icon="eye" :inset="true" />
                                    <flux:button href="{{ route('organization.teams.transfer', $team) }}" variant="ghost"
                                        size="sm" icon="user-plus" :inset="true" />
                                    <flux:button href="{{ route('organization.teams.edit', $team) }}" variant="ghost"
                                        size="sm" icon="pencil-square" :inset="true" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-12">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon.user-group class="w-12 h-12 text-zinc-300" />
                                    <span class="text-zinc-500">No se encontraron equipos que coincidan con la
                                        búsqueda.</span>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>