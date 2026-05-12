<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Gestión de Actividades Intradía</flux:heading>
            <flux:subheading>Asignación de reuniones, capacitaciones y actividades no productivas.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openAssignmentModal">
            Programar Actividad
        </flux:button>
    </div>

    <flux:card>
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <flux:input type="date" wire:model.live="date" label="Fecha de Gestión" class="w-full md:w-64" />
            
            <flux:select wire:model.live="selectedTeamId" label="Filtrar por Equipo" placeholder="Todos los equipos" class="w-full md:w-64">
                <option value="">Todos los equipos</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
            </flux:select>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Operador</flux:table.column>
                <flux:table.column>Actividad</flux:table.column>
                <flux:table.column>Horario</flux:table.column>
                <flux:table.column>Duración</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($activities as $activity)
                    <flux:table.row :key="$activity->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$activity->employee->full_name" size="sm" />
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ $activity->employee->full_name }}</span>
                                    <span class="text-xs text-zinc-500">{{ $activity->employee->team->name ?? 'Sin equipo' }}</span>
                                </div>
                            </div>
                        </flux:cell>

                        <flux:table.cell>
                            <flux:badge :color="$activity->activityType->color ?? 'zinc'" inset="top bottom">
                                {{ $activity->activityType->name }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2 text-sm text-zinc-600">
                                <flux:icon icon="clock" variant="micro" />
                                {{ \Carbon\Carbon::parse($activity->getRangeStart())->format('H:i') }} - 
                                {{ \Carbon\Carbon::parse($activity->getRangeEnd())->format('H:i') }}
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm text-zinc-500">
                                {{ \Carbon\Carbon::parse($activity->getRangeStart())->diffInMinutes($activity->getRangeEnd()) }} min
                            </span>
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteActivity({{ $activity->id }})" wire:confirm="¿Estás seguro de eliminar esta actividad?" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-12">
                            <flux:icon icon="calendar-days" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-700" />
                            <flux:text class="mt-4">No hay actividades programadas para esta fecha</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    </flux:card>

    {{-- Modal de Asignación --}}
    <flux:modal wire:model="showAssignmentModal" class="md:w-[600px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Programar Nueva Actividad</flux:heading>
                <flux:subheading>Asigna una actividad masiva o individual.</flux:subheading>
            </div>

            <form wire:submit="assignActivity" class="space-y-4">
                <flux:select wire:model.live="activityDefinitionId" label="Definición de Actividad" placeholder="Seleccione una plantilla...">
                    @foreach($definitions as $definition)
                        <option value="{{ $definition->id }}">{{ $definition->name }}</option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input type="time" wire:model.live="startTime" label="Hora Inicio" />
                    <flux:input type="time" wire:model="endTime" label="Hora Fin" />
                </div>

                <div class="space-y-2">
                    <flux:label>Seleccionar Operadores</flux:label>
                    <div class="max-h-48 overflow-y-auto border rounded-lg p-2 space-y-1">
                        @foreach($employees as $employee)
                            <label class="flex items-center gap-2 p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded cursor-pointer">
                                <input type="checkbox" wire:model="selectedEmployeeIds" value="{{ $employee->id }}" class="rounded border-zinc-300">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium">{{ $employee->full_name }}</span>
                                    <span class="text-[10px] text-zinc-500">{{ $employee->team->name ?? 'Sin equipo' }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('selectedEmployeeIds') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="$set('showAssignmentModal', false)">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Guardar Asignación</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
