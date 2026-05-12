<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Departamentos</h1>
                <flux:link href="{{ route('organization.departments.create') }}" variant="primary">
                    Nuevo Departamento
                </flux:link>
            </div>
        </div>

        <div class="p-6">
            <!-- Filtros -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o descripción..."
                        label="Buscar" />
                </div>
                <div>
                    <label for="directorateFilter"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dirección</label>
                    <select wire:model.live="directorateFilter" id="directorateFilter"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Todas las direcciones</option>
                        @foreach($this->directorates as $directorate)
                            <option value="{{ $directorate->id }}">{{ $directorate->name }}</option>
                        @endforeach
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
                <flux:table :paginate="$departments">
                    <flux:table.columns>
                        <flux:table.column>Nombre</flux:table.column>
                        <flux:table.column>Dirección</flux:table.column>
                        <flux:table.column>Descripción</flux:table.column>
                        <flux:table.column>Equipos</flux:table.column>
                        <flux:table.column>Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($departments as $department)
                            <flux:table.row :key="$department->id">
                                <flux:table.cell>
                                    <div class="font-medium text-gray-900">{{ $department->name }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $department->directorate->name }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="max-w-xs truncate">{{ $department->description }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $department->teams_count ?? 0 }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button.group>
                                        <flux:button href="{{ route('organization.departments.show', $department) }}"
                                            variant="ghost" size="sm" title="Ver departamento">
                                            <flux:icon.eye class="w-4 h-4" />
                                        </flux:button>
                                        <flux:button href="{{ route('organization.departments.edit', $department) }}"
                                            variant="ghost" size="sm" title="Editar departamento">
                                            <flux:icon.pencil-square class="w-4 h-4" />
                                        </flux:button>
                                    </flux:button.group>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center py-8 text-gray-500">
                                    No se encontraron departamentos.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <!-- Paginación -->
            @if($departments->hasPages())
                <div class="mt-6">
                    {{ $departments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
