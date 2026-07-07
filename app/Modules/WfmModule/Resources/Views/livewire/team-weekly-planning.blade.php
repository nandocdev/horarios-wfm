<div class="p-6">
    <div class="flex items-center gap-4">
        <flux:button href="{{ route('schedules.planning.teams', ['week' => $week->id]) }}" icon="arrow-left"
            variant="ghost" wire:navigate />
        <div class="flex-1">
            <flux:heading size="xl">{{ __('Planificación de Equipo') }}: {{ $team->name }}</flux:heading>
            <flux:subheading class="mt-1">
                {{ __('Semana del') }} {{ $week->week_start_date->format('d M') }} {{ __('al') }}
                {{ $week->week_end_date->format('d M, Y') }}
            </flux:subheading>
        </div>
        <flux:button wire:click="$set('showBulkAssignModal', true)" variant="primary" icon="users">
            {{ __('Asignación Masiva') }}
        </flux:button>
    </div>

    <div class="mt-8">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sticky>{{ __('Empleado') }}</flux:table.column>
                @foreach($days as $dayNum => $dayName)
                    <flux:table.column align="center">{{ $dayName }}</flux:table.column>
                @endforeach
                <flux:table.column align="end">{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($employees as $employee)
                    <flux:table.row :key="$employee->id">
                        <flux:table.cell sticky class="bg-white dark:bg-zinc-900">
                            <div class="flex items-center gap-3">
                                <flux:avatar initials="{{ $employee->initials }}" size="xs" />
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium">{{ $employee->full_name }}</span>
                                    <span class="text-xs text-zinc-500">{{ $employee->position?->name }}</span>
                                </div>
                            </div>
                        </flux:table.cell>

                        @foreach($days as $dayNum => $dayName)
                            @php
                                $currentDate = $week->week_start_date->copy()->addDays($dayNum - 1);
                                
                                // Buscar si hay una excepción para este empleado y este día
                                $dayException = $exceptionsByEmployee->get($employee->id)?->first(function($ex) use ($currentDate) {
                                    return $currentDate->between(
                                        $ex->start_at->startOfDay(), 
                                        $ex->end_at->endOfDay()
                                    );
                                });

                                $assignment = $assignmentsByEmployee->get($employee->id)?->firstWhere('day_of_week', $dayNum);
                            @endphp
                            <flux:table.cell align="center" class="{{ $dayException ? 'bg-zinc-50/50 dark:bg-zinc-800/30' : '' }}">
                                @if($dayException)
                                    <div class="flex flex-col items-center justify-center p-2 rounded-md border border-dashed border-zinc-200 dark:border-zinc-700 opacity-80" 
                                         title="{{ $dayException->remarks }}">
                                        <flux:badge :color="$dayException->reason?->color ?? 'zinc'" size="sm" class="font-bold uppercase text-[9px]">
                                            {{ $dayException->reason?->name ?? __('EXCEPCIÓN') }}
                                        </flux:badge>
                                        <span class="mt-1 text-[10px] text-zinc-500 font-medium italic">
                                            {{ __('Bloqueado') }}
                                        </span>
                                    </div>
                                @elseif($assignment)
                                    <button wire:click="editAssignment({{ $assignment->id }})"
                                        class="group flex flex-col items-center justify-center rounded-md border border-transparent p-2 transition-opacity hover:border-zinc-200 hover:bg-zinc-50 dark:hover:border-zinc-700 dark:hover:bg-zinc-800">
                                        <span
                                            class="text-[10px] font-bold uppercase text-zinc-400">{{ $assignment->schedule?->name ?? 'OFF' }}</span>
                                        <span class="text-xs font-semibold">
                                            {{ $this->formatTime($assignment->start_time) ?? ($assignment->schedule ? $this->formatTime($assignment->schedule->start_time) : '--') }}
                                            -
                                            {{ $this->formatTime($assignment->end_time) ?? ($assignment->schedule ? $this->formatTime($assignment->schedule->end_time) : '--') }}
                                        </span>
                                        <div class="mt-1 flex gap-1">
                                            @if($assignment->lunch_start_time)
                                                <flux:icon icon="fire" size="xs" class="text-orange-500" />
                                            @endif
                                            @if($assignment->break_start_time)
                                                <flux:icon icon="clock" size="xs" class="text-blue-500" />
                                            @endif
                                        </div>
                                    </button>
                                @else
                                    <span class="text-xs text-zinc-400">--</span>
                                @endif
                            </flux:table.cell>
                        @endforeach

                        <flux:table.cell align="end">
                            <flux:button
                                href="{{ route('schedules.planning.employee', ['week' => $week->id, 'employee' => $employee->id]) }}"
                                variant="ghost" size="sm" icon="pencil-square" wire:navigate>
                                {{ __('Editar Semana') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $employees->links() }}
        </div>
    </div>

    <!-- Modal de Edición Individual -->
    <flux:modal wire:model="showEditModal" class="min-w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Editar Asignación Individual') }}</flux:heading>
                <flux:subheading>{{ __('Ajuste de turno, almuerzo y descanso para este día.') }}</flux:subheading>
            </div>

            <form wire:submit="saveAssignment" class="space-y-4">
                <flux:select wire:model.live="editForm.schedule_id" label="{{ __('Turno') }}">
                    @foreach($schedules as $sched)
                        @if(in_array($selectedDayOfWeek, $sched->allowed_days ?? []))
                            <flux:select.option value="{{ $sched->id }}">
                                {{ $sched->name }} ({{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }})
                            </flux:select.option>
                        @endif
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="editForm.start_time" type="time" icon="clock" label="{{ __('Entrada') }}" />
                    <flux:input wire:model="editForm.end_time" type="time" icon="clock" label="{{ __('Salida') }}" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="editForm.lunch_start_time" type="time" icon="clock"
                        label="{{ __('Inicio Almuerzo') }}" />
                    <flux:input wire:model="editForm.break_start_time" type="time" icon="clock"
                        label="{{ __('Inicio Descanso') }}" />
                </div>

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="$set('showEditModal', false)">{{ __('Cancelar') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Guardar Cambios') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Modal de Asignación Masiva -->
    <flux:modal wire:model="showBulkAssignModal" class="min-w-[450px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Asignación Masiva para Equipo') }}</flux:heading>
                <flux:subheading>
                    {{ __('Aplicará el horario seleccionado a todos los miembros activos del equipo para toda la semana.') }}
                </flux:subheading>
            </div>

            <form wire:submit="bulkAssign" class="space-y-4">
                <flux:select wire:model.live="bulkForm.schedule_id" label="{{ __('Turno Base') }}"
                    placeholder="{{ __('Seleccionar turno...') }}">
                    @foreach($schedules as $sched)
                        <flux:select.option value="{{ $sched->id }}">
                            {{ $sched->name }} ({{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="bulkForm.start_time" type="time" icon="clock" label="{{ __('Entrada') }}" />
                    <flux:input wire:model="bulkForm.end_time" type="time" icon="clock" label="{{ __('Salida') }}" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="bulkForm.lunch_start_time" type="time" icon="clock"
                        label="{{ __('Inicio Almuerzo') }}" />
                    <flux:input wire:model="bulkForm.break_start_time" type="time" icon="clock"
                        label="{{ __('Inicio Descanso') }}" />
                </div>

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="$set('showBulkAssignModal', false)">{{ __('Cancelar') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary" icon="check">{{ __('Aplicar a todo el equipo') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>