<div class="space-y-8">
    <!-- Filtros -->
    <flux:card class="space-y-4">
        <div>
            <flux:heading size="md">Filtros</flux:heading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:input label="Buscar" wire:model.live.debounce.300ms="search"
                placeholder="Nombre, apellido, email..." />

            <flux:select label="Departamento" wire:model.live="department_id" placeholder="Todos los departamentos">
                <flux:select.option value="">Todos los departamentos</flux:select.option>
                @foreach($filterOptions['departments'] as $id => $name)
                    <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select label="Posición" wire:model.live="position_id" placeholder="Todas las posiciones">
                <flux:select.option value="">Todas las posiciones</flux:select.option>
                @foreach($filterOptions['positions'] as $id => $name)
                    <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select label="Estado" wire:model.live="is_active" placeholder="Todos los estados">
                <flux:select.option value="">Todos los estados</flux:select.option>
                <flux:select.option value="1">Activo</flux:select.option>
                <flux:select.option value="0">Inactivo</flux:select.option>
            </flux:select>

            <flux:input label="Ingreso desde" type="date" wire:model.live="date_from" />
            <flux:input label="Ingreso hasta" type="date" wire:model.live="date_to" />
        </div>

        <div class="flex justify-between items-center mt-4 gap-4 flex-wrap">
            <div class="flex items-center gap-4">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="radio" value="1" wire:model.live="exportAll">
                    Exportar todos (filtrados)
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="radio" value="0" wire:model.live="exportAll">
                    Exportar seleccionados
                </label>
            </div>

            <div class="flex items-center gap-2">
                <flux:button wire:click="syncWithCisco" variant="outline" icon="arrow-path" wire:loading.attr="disabled">
                    Sincronizar Cisco
                </flux:button>
                <flux:button as="a" href="{{ $csvExportUrl }}" target="_blank" variant="outline">Export CSV
                </flux:button>
                <flux:button as="a" href="{{ $excelExportUrl }}" target="_blank" variant="outline">Export Excel
                </flux:button>
                <flux:button wire:click="clearFilters" variant="outline">Limpiar filtros</flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Tabla de empleados -->
    <flux:card class="space-y-4">
        <div>
            <flux:heading size="md">Empleados</flux:heading>
            <flux:subheading>{{ $employees->total() }} empleados encontrados</flux:subheading>
        </div>

        <div class="space-y-4">
            @if($employees->count() > 0)
                <flux:table :paginate="$employees">
                    <flux:table.columns class="sticky top-0 z-10 bg-white">
                        <flux:table.column class="sticky top-0 z-10 bg-white">
                            <input type="checkbox" wire:model.live="selectAll" />
                        </flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Número</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Nombre</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Email</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Departamento</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Cargo</flux:table.column>
                        <flux:table.column class="sticky top-0 z-10 bg-white">Estado</flux:table.column>
                        <flux:table.column align="end" class="sticky top-0 z-10 bg-white">Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                        @forelse($employees as $employee)
                            <flux:table.row :key="$employee->id" class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                                <flux:table.cell class="py-2">
                                    <input type="checkbox" wire:model.live="selected" value="{{ $employee->id }}"
                                        @disabled($exportAll) />
                                </flux:table.cell>
                                <flux:table.cell class="py-2">{{ $employee->employee_number }}</flux:table.cell>
                                <flux:table.cell class="py-2">
                                    <div class="flex items-center gap-4">
                                        <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-slate-600">
                                                {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                            </span>
                                        </div>
                                        <flux:text class="font-medium text-slate-900">{{ $employee->full_name }}</flux:text>
                                        @if($employee->is_manager)
                                            <flux:badge size="xs" variant="ghost">Manager</flux:badge>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="py-2">{{ $employee->email }}</flux:table.cell>
                                <flux:table.cell class="py-2">{{ str($employee->department?->name)->limit(25) ?? 'Sin asignar' }}
                                </flux:table.cell>
                                <flux:table.cell class="py-2">{{ $employee->position?->name ?? 'Sin asignar' }}</flux:table.cell>
                                <flux:table.cell class="py-2">
                                    <flux:badge :variant="$employee->is_active ? 'success' : 'danger'">
                                        {{ $employee->is_active ? 'Activo' : 'Inactivo' }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end" class="py-2">
                                    <flux:button.group>
                                        @can('view', $employee)
                                            <flux:button href="{{ route('employees.show', $employee) }}" variant="ghost" size="sm"
                                                title="Ver" wire:navigate>
                                                <flux:icon.eye class="w-4 h-4" />
                                            </flux:button>
                                        @endcan

                                        @can('update', $employee)
                                            <flux:button href="{{ route('employees.edit', $employee) }}" variant="ghost" size="sm"
                                                title="Editar" wire:navigate>
                                                <flux:icon.pencil-square class="w-4 h-4" />
                                            </flux:button>
                                        @endcan
                                    </flux:button.group>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                                <flux:table.cell colspan="8" class="text-center py-8">
                                    <flux:text size="sm" class="text-slate-500 italic">No se encontraron empleados con esos
                                        criterios.</flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>

                <!-- Paginación -->
                <div class="mt-8">
                    {{ $employees->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-slate-500">
                        <p class="text-xl font-semibold">No se encontraron empleados</p>
                        <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                    </div>
                </div>
            @endif
        </div>
    </flux:card>
</div>
