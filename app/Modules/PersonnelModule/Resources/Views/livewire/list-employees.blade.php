<div class="space-y-6">
    <x-wfm.page-header title="Empleados" description="Gestión del personal de la institución." search searchWire="search" searchPlaceholder="Buscar nombre, email o número...">
        <x-slot:actions>
            <flux:button wire:click="syncWithCisco" variant="ghost" icon="arrow-path" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="syncWithCisco">Sincronizar Cisco</span>
                <span wire:loading wire:target="syncWithCisco">Sincronizando...</span>
            </flux:button>
            <flux:dropdown>
                <flux:button variant="ghost" icon="arrow-down-tray">Exportar</flux:button>
                <flux:menu>
                    <flux:menu.item :href="$csvExportUrl" target="_blank">CSV</flux:menu.item>
                    <flux:menu.item :href="$excelExportUrl" target="_blank">Excel</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            <flux:button href="{{ route('employees.import') }}" variant="ghost" icon="arrow-up-tray" wire:navigate>Importar</flux:button>
            <flux:button href="{{ route('employees.create') }}" variant="primary" icon="plus" wire:navigate>Nuevo</flux:button>
        </x-slot:actions>
        <x-slot:filters>
            <x-wfm.filter-bar clear clearWire="clearFilters">
                <flux:select wire:model.live="department_id" placeholder="Departamento" class="w-full sm:!w-44">
                    <flux:select.option value="">Todos los departamentos</flux:select.option>
                    @foreach($filterOptions['departments'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="position_id" placeholder="Posición" class="w-full sm:!w-44">
                    <flux:select.option value="">Todas las posiciones</flux:select.option>
                    @foreach($filterOptions['positions'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="is_active" placeholder="Estado" class="w-full sm:!w-32">
                    <flux:select.option value="">Todos</flux:select.option>
                    <flux:select.option value="1">Activo</flux:select.option>
                    <flux:select.option value="0">Inactivo</flux:select.option>
                </flux:select>

                <flux:input type="date" wire:model.live="date_from" label="Desde" class="w-full sm:!w-36" />
                <flux:input type="date" wire:model.live="date_to" label="Hasta" class="w-full sm:!w-36" />
            </x-wfm.filter-bar>
        </x-slot:filters>
    </x-wfm.page-header>

    <x-wfm.section :title="'Empleados (' . $employees->total() . ')'">
        <div wire:loading.delay.class="opacity-50" class="transition-opacity">
            <x-wfm.table :headers="[
                ['label' => ''],
                ['label' => 'Número'],
                ['label' => 'Nombre'],
                ['label' => 'Email'],
                ['label' => 'Departamento'],
                ['label' => 'Cargo'],
                ['label' => 'Estado'],
                ['label' => 'Acciones', 'align' => 'end'],
            ]" :loading="$employees === null" empty="No se encontraron empleados con esos criterios.">
                @forelse($employees as $employee)
                    <flux:table.row :key="$employee->id">
                        <flux:table.cell>
                            <flux:checkbox wire:model.live="selected" value="{{ $employee->id }}" :disabled="$exportAll" />
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $employee->employee_number }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:avatar :name="$employee->full_name" :initials="$employee->initials" size="xs" />
                                <div>
                                    <p class="text-sm font-medium text-wfm-navy-800 dark:text-white">{{ $employee->full_name }}</p>
                                    @if($employee->is_manager)
                                        <x-wfm.agent-status status="available" label="Manager" size="xs" />
                                    @endif
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $employee->email }}</flux:table.cell>
                        <flux:table.cell class="text-xs">{{ str($employee->department?->name)->limit(25) ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $employee->position?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <x-wfm.agent-status :status="$employee->is_active ? 'available' : 'offline'" :label="$employee->is_active ? 'Activo' : 'Inactivo'" size="xs" />
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:button.group>
                                @can('view', $employee)
                                    <flux:button href="{{ route('employees.show', $employee) }}" variant="ghost" size="sm" icon="eye" wire:navigate />
                                @endcan
                                @can('update', $employee)
                                    <flux:button href="{{ route('employees.edit', $employee) }}" variant="ghost" size="sm" icon="pencil-square" wire:navigate />
                                @endcan
                            </flux:button.group>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">
                            <x-wfm.empty icon="user-group" message="No se encontraron empleados" description="Intenta ajustar los filtros de búsqueda." />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-wfm.table>
        </div>

        <div class="mt-4">
            {{ $employees->links() }}
        </div>
    </x-wfm.section>
</div>
