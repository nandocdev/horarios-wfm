<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-md shadow-sm border border-slate-200">
        <div class="p-4 border-b border-slate-200">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-slate-900">Direcciones</h1>
                <flux:link href="{{ route('organization.directorates.create') }}" variant="primary">
                    Nueva Dirección
                </flux:link>
            </div>
        </div>

        <div class="p-4">
            <!-- Filtros -->
            <div class="mb-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o descripción..."
                        label="Buscar" />
                </div>
                <div>
                    <label for="activeFilter"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Estado</label>
                    <select wire:model.live="activeFilter" id="activeFilter"
                        class="block w-full px-4 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                        <option value="">Todos los estados</option>
                        <option value="1">Activas</option>
                        <option value="0">Inactivas</option>
                    </select>
                </div>
                <div>
                    <label for="perPage" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Por
                        página</label>
                    <select wire:model.live="perPage" id="perPage"
                        class="block w-full px-4 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto">
                <flux:table :paginate="$directorates">
                    <flux:table.columns class="sticky top-0 z-10 bg-white">
                        <flux:table.column class="sticky top-0 z-10 bg-white">Nombre</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Descripción</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Estado</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Departamentos</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                        @forelse($directorates as $directorate)
                            <flux:table.row :key="$directorate->id" class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                                <flux:table.cell class="py-2">
                                    <div class="font-medium text-slate-900">{{ $directorate->name }}</div>
                                </flux:table.cell>
                                <flux:table.cell class="py-2">
                                    <div class="max-w-xs truncate">{{ $directorate->description }}</div>
                                </flux:table.cell>
                                <flux:table.cell class="py-2">
                                    @if($directorate->is_active)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-green-50 border border-green-200 text-green-600">
                                            Activa
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-red-50 border border-red-200 text-red-600">
                                            Inactiva
                                        </span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="py-2">
                                    {{ $directorate->departments_count ?? 0 }}
                                </flux:table.cell>
                                <flux:table.cell class="py-2">
                                    <flux:button.group>
                                        <flux:button href="{{ route('organization.directorates.show', $directorate) }}"
                                            variant="ghost" size="sm" title="Ver dirección">
                                            <flux:icon.eye class="w-4 h-4" />
                                        </flux:button>
                                        <flux:button href="{{ route('organization.directorates.edit', $directorate) }}"
                                            variant="ghost" size="sm" title="Editar dirección">
                                            <flux:icon.pencil-square class="w-4 h-4" />
                                        </flux:button>
                                    </flux:button.group>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                                <flux:table.cell colspan="5" class="text-center py-8 text-slate-500">
                                    No se encontraron direcciones.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <!-- Paginación -->
            @if($directorates->hasPages())
                <div class="mt-8">
                    {{ $directorates->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
