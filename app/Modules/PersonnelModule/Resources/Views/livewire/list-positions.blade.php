<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Posiciones</h1>
                <flux:link href="{{ route('organization.positions.create') }}" variant="primary">
                    Nueva Posición
                </flux:link>
            </div>
        </div>

        <div class="p-6">
            <!-- Filtros -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o descripción..."
                        label="Buscar" />
                </div>
                <div>
                    <label for="departmentFilter"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Departamento</label>
                    <select wire:model.live="departmentFilter" id="departmentFilter"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Todos los departamentos</option>
                        @foreach($this->departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="activeFilter"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estado</label>
                    <select wire:model.live="activeFilter" id="activeFilter"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Todos los estados</option>
                        <option value="1">Activas</option>
                        <option value="0">Inactivas</option>
                    </select>
                </div>
                <div>
                    <label for="perPage" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Por
                        página</label>
                    <select wire:model.live="perPage" id="perPage"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto">
                <flux:table :paginate="$positions">
                    <flux:table.columns>
                        <flux:table.column>Nombre</flux:table.column>
                        <flux:table.column>Departamento</flux:table.column>
                        <flux:table.column>Dirección</flux:table.column>
                        <flux:table.column>Estado</flux:table.column>
                        <flux:table.column>Empleados</flux:table.column>
                        <flux:table.column>Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($positions as $position)
                            <flux:table.row :key="$position->id">
                                <flux:table.cell>
                                    <div class="font-medium text-gray-900">{{ $position->name }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $position->department->name }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $position->department->directorate->name }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($position->is_active)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Activa
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Inactiva
                                        </span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $position->users_count ?? 0 }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button.group>
                                        <flux:button href="{{ route('organization.positions.show', $position) }}"
                                            variant="ghost" size="sm" title="Ver posición">
                                            <flux:icon.eye class="w-4 h-4" />
                                        </flux:button>
                                        <flux:button href="{{ route('organization.positions.edit', $position) }}"
                                            variant="ghost" size="sm" title="Editar posición">
                                            <flux:icon.pencil-square class="w-4 h-4" />
                                        </flux:button>
                                    </flux:button.group>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="text-center py-8 text-gray-500">
                                    No se encontraron posiciones.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <!-- Paginación -->
            @if($positions->hasPages())
                <div class="mt-6">
                    {{ $positions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
