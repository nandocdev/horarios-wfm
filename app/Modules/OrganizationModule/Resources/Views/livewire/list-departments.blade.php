<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-md shadow-sm border border-slate-200">
        <div class="p-4 border-b border-slate-200">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-slate-900">Departamentos</h1>
                <flux:link href="{{ route('organization.departments.create') }}" variant="primary">
                    Nuevo Departamento
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
                    <label for="directorateFilter"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Dirección</label>
                    <select wire:model.live="directorateFilter" id="directorateFilter"
                        class="block w-full px-4 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                        <option value="">Todas las direcciones</option>
                        @foreach($this->directorates as $directorate)
                            <option value="{{ $directorate->id }}">{{ $directorate->name }}</option>
                        @endforeach
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
                <flux:table :paginate="$departments">
                    <flux:table.columns class="sticky top-0 z-10 bg-white">
                        <flux:table.column class="sticky top-0 z-10 bg-white">Nombre</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Dirección</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Descripción</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Equipos</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                        @forelse($departments as $department)
                            <flux:table.row :key="$department->id" class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                                <flux:table.cell class="py-2">
                                    <div class="font-medium text-slate-900">{{ $department->name }}</div>
                                </flux:table.cell>
                                <flux:table.cell class="py-2">
                                    {{ $department->directorate->name }}
                                </flux:table.cell>
                                <flux:table.cell class="py-2">
                                    <div class="max-w-xs truncate">{{ $department->description }}</div>
                                </flux:table.cell>
                                <flux:table.cell class="py-2">
                                    {{ $department->teams_count ?? 0 }}
                                </flux:table.cell>
                                <flux:table.cell class="py-2">
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
                            <flux:table.row class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                                <flux:table.cell colspan="5" class="text-center py-8 text-slate-500">
                                    No se encontraron departamentos.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <!-- Paginación -->
            @if($departments->hasPages())
                <div class="mt-8">
                    {{ $departments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
