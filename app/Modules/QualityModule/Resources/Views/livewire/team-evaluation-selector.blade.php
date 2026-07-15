<div>
    <div>
        <flux:heading size="xl" level="1">Nueva Evaluación</flux:heading>
        <flux:subheading>Seleccione el equipo y empleado a evaluar</flux:subheading>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="col-span-1">
            <flux:card>
                <flux:heading size="md" class="mb-4">Equipos</flux:heading>
                <div class="space-y-2">
                    @foreach($teams as $team)
                        <button 
                            wire:click="$set('selectedTeamId', '{{ $team->id }}')"
                            class="w-full text-left px-3 py-2 rounded-md transition-colors {{ $selectedTeamId == $team->id ? 'bg-blue-50 text-blue-700 font-medium dark:bg-blue-900/30 dark:text-blue-400' : 'hover:bg-slate-50 dark:hover:bg-slate-800' }}"
                        >
                            {{ $team->name }}
                        </button>
                    @endforeach
                </div>
            </flux:card>
        </div>

        <div class="col-span-1 md:col-span-3">
            <flux:card>
                @if(!$selectedTeamId)
                    <div class="py-12 text-center text-slate-500">
                        Seleccione un equipo para ver los empleados.
                    </div>
                @else
                    <flux:heading size="md" class="mb-4">Empleados del Equipo</flux:heading>
                    
                    @if($employees->isEmpty())
                        <div class="py-12 text-center text-slate-500">
                            No hay empleados en este equipo.
                        </div>
                    @else
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Empleado</flux:table.column>
                                <flux:table.column>Usuario</flux:table.column>
                                <flux:table.column>Evaluaciones (Semana)</flux:table.column>
                                <flux:table.column></flux:table.column>
                            </flux:table.columns>
                            
                            <flux:table.rows>
                                @foreach($employees as $employee)
                                    <flux:table.row>
                                        <flux:table.cell class="font-medium">
                                            {{ $employee->full_name }}
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            {{ $employee->username }}
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:badge color="{{ $employee->current_week_evaluations_count > 0 ? 'success' : 'zinc' }}">
                                                {{ $employee->current_week_evaluations_count }}
                                            </flux:badge>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex justify-end gap-2">
                                                <flux:button href="{{ route('quality.evaluations.index', ['employeeFilter' => $employee->id]) }}" size="sm" variant="subtle" icon="eye">
                                                    Ver Evaluaciones
                                                </flux:button>
                                                <flux:button href="{{ route('quality.evaluations.form', $employee) }}" size="sm" variant="primary" icon="plus">
                                                    Nueva Evaluación
                                                </flux:button>
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @endif
                @endif
            </flux:card>
        </div>
    </div>
</div>
