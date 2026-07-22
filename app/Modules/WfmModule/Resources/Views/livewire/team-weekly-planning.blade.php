<div class="p-6 lg:p-8 mx-auto flex-1 flex flex-col">
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

    @php
        $weeklyTotals = [];
        $dayNames = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
    @endphp

    <div class="mt-8 flex-1 overflow-x-auto">
        <flux:table>
            <flux:table.columns class="sticky top-0 z-10 bg-white dark:bg-zinc-900">
                <flux:table.column sticky class="w-48">{{ __('Empleado') }}</flux:table.column>
                @foreach($days as $dayNum => $dayName)
                    <flux:table.column align="center" class="min-w-[100px]">{{ $dayName }}</flux:table.column>
                @endforeach
                <flux:table.column align="center" class="w-24 text-xs font-bold text-slate-500 uppercase">{{ __('Total') }}</flux:table.column>
                <flux:table.column align="end" class="w-24">{{ __('Acciones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($employees as $employee)
                    @php
                        $empHours = 0;
                    @endphp
                    <flux:table.row :key="$employee->id" class="hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                        <flux:table.cell sticky class="py-1 bg-white dark:bg-zinc-900">
                            <div class="flex items-center gap-2">
                                <flux:avatar initials="{{ $employee->initials }}" size="xs" />
                                <div class="flex flex-col leading-tight">
                                    <span class="text-sm font-medium">{{ $employee->full_name }}</span>
                                    <span class="text-[10px] text-slate-500">{{ $employee->position?->name }}</span>
                                </div>
                            </div>
                        </flux:table.cell>

                        @foreach($days as $dayNum => $dayName)
                            @php
                                $currentDate = $week->week_start_date->copy()->addDays($dayNum - 1);
                                $dayException = $exceptionsByEmployee->get($employee->id)?->first(function ($ex) use ($currentDate) {
                                    return $currentDate->between($ex->start_at->startOfDay(), $ex->end_at->endOfDay());
                                });
                                $assignment = $assignmentsByEmployee->get($employee->id)?->firstWhere('day_of_week', $dayNum);
                                $dayMinutes = 0;
                                if ($assignment && $assignment->start_time && $assignment->end_time) {
                                    $s = \Carbon\Carbon::parse($assignment->start_time);
                                    $e = \Carbon\Carbon::parse($assignment->end_time);
                                    if ($e->lessThan($s)) $e->addDay();
                                    $dayMinutes = $s->diffInMinutes($e);
                                    if ($assignment->lunch_start_time && $assignment->lunch_end_time) {
                                        $ls = \Carbon\Carbon::parse($assignment->lunch_start_time);
                                        $le = \Carbon\Carbon::parse($assignment->lunch_end_time);
                                        $dayMinutes -= $ls->diffInMinutes($le);
                                    }
                                }
                                $empHours += $dayMinutes;
                            @endphp
                            <flux:table.cell align="center" class="py-1 {{ $dayException ? 'bg-slate-50/50 dark:bg-zinc-800/30' : '' }}">
                                @if($dayException)
                                    <div class="flex flex-col items-center p-1.5 rounded-md border border-dashed border-slate-200 dark:border-zinc-700 opacity-80"
                                        title="{{ $dayException->remarks }}">
                                        <flux:badge :color="$dayException->reason?->color ?? 'slate'" size="sm" class="font-bold uppercase text-[9px]">
                                            {{ $dayException->reason?->name ?? 'EXC' }}
                                        </flux:badge>
                                    </div>
                                @elseif($assignment)
                                    <button wire:click="editAssignment({{ $assignment->id }})"
                                        class="group relative flex flex-col items-center rounded-md border border-transparent p-1.5 transition-all hover:border-slate-200 hover:bg-slate-50 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/50">
                                        <span class="text-[10px] font-bold uppercase text-slate-400">{{ $assignment->schedule?->name ?? 'OFF' }}</span>
                                        <span class="text-xs font-semibold whitespace-nowrap">
                                            {{ $this->formatTime($assignment->start_time) ?? '--' }}–{{ $this->formatTime($assignment->end_time) ?? '--' }}
                                        </span>
                                        <div class="flex gap-1 mt-0.5">
                                            @if($assignment->lunch_start_time)<flux:icon.fire size="xs" class="text-amber-500" />@endif
                                            @if($assignment->break_start_time)<flux:icon.clock size="xs" class="text-blue-500" />@endif
                                        </div>
                                        <span class="text-[9px] text-slate-400 font-mono mt-0.5">{{ intdiv($dayMinutes, 60) }}h {{ $dayMinutes % 60 }}m</span>
                                        {{-- Copy button overlay --}}
                                        <button wire:click.stop="copyDaySetup({{ $employee->id }}, {{ $dayNum }})"
                                            class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-slate-200 dark:bg-zinc-700 hover:bg-blue-400 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                            title="Copiar este día">
                                            <flux:icon.document-duplicate size="3xs" />
                                        </button>
                                    </button>
                                @else
                                    <button wire:click.stop="copyDaySetup({{ $employee->id }}, {{ $dayNum }})"
                                        class="text-[10px] text-slate-300 hover:text-blue-400 transition-colors p-1" title="Copiar desde otro día">
                                        <flux:icon.document-duplicate size="3xs" />
                                    </button>
                                @endif
                            </flux:table.cell>
                        @endforeach

                        <flux:table.cell align="center" class="py-1">
                            <span class="font-mono text-sm font-bold">{{ intdiv($empHours, 60) }}h {{ $empHours % 60 }}m</span>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="py-1">
                            <flux:button href="{{ route('schedules.planning.employee', ['week' => $week->id, 'employee' => $employee->id]) }}"
                                variant="ghost" size="sm" icon="pencil-square" wire:navigate />
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
    <flux:modal wire:model="showEditModal" class="w-full max-w-md">
        <div class="space-y-4">
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

    <!-- Modal de Copiar Día -->
    <flux:modal wire:model="showCopyModal" class="w-full max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Copiar Día') }}</flux:heading>
                <flux:subheading>{{ __('Seleccione los días destino para copiar la asignación.') }}</flux:subheading>
            </div>
            <div class="space-y-2">
                <flux:text class="text-sm font-medium text-slate-700 dark:text-zinc-300">
                    {{ __('Copiar desde') }}: <strong>{{ $dayNames[$copySourceDay] ?? '' }}</strong>
                </flux:text>
                @foreach($days as $dayNum => $dayName)
                    @if($dayNum !== $copySourceDay)
                        <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-slate-50 dark:hover:bg-zinc-800 cursor-pointer">
                            <flux:checkbox wire:model="copyTargetDays" value="{{ $dayNum }}" />
                            <span class="text-sm">{{ $dayName }}</span>
                        </label>
                    @endif
                @endforeach
            </div>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showCopyModal', false)">{{ __('Cancelar') }}</flux:button>
                <flux:button wire:click="copyDayExecute" variant="primary" icon="document-duplicate">
                    {{ __('Copiar') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal de Asignación Masiva -->
    <flux:modal wire:model="showBulkAssignModal" class="w-full max-w-lg">
        <div class="space-y-4">
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

    {{-- Sticky Footer con Totales --}}
    @php
        $grandTotalMinutes = 0;
        $employeeCount = 0;
        foreach($employees as $emp) {
            $empMin = 0;
            foreach($days as $dayNum => $dayName) {
                $asg = $assignmentsByEmployee->get($emp->id)?->firstWhere('day_of_week', $dayNum);
                if ($asg && $asg->start_time && $asg->end_time) {
                    $s = \Carbon\Carbon::parse($asg->start_time);
                    $e = \Carbon\Carbon::parse($asg->end_time);
                    if ($e->lessThan($s)) $e->addDay();
                    $d = $s->diffInMinutes($e);
                    if ($asg->lunch_start_time && $asg->lunch_end_time) {
                        $ls = \Carbon\Carbon::parse($asg->lunch_start_time);
                        $le = \Carbon\Carbon::parse($asg->lunch_end_time);
                        $d -= $ls->diffInMinutes($le);
                    }
                    $empMin += $d;
                }
            }
            $grandTotalMinutes += $empMin;
            $employeeCount++;
        }
        $avgDaily = $employeeCount > 0 ? intdiv($grandTotalMinutes, max($employeeCount * 7, 1)) : 0;
    @endphp
    <div class="sticky bottom-0 bg-white/95 dark:bg-zinc-900/95 backdrop-blur border-t border-slate-200 dark:border-zinc-700 p-3 flex items-center justify-between text-sm z-20">
        <div class="flex gap-6 text-slate-600 dark:text-zinc-400">
            <span><strong>{{ $employeeCount }}</strong> {{ __('empleados') }}</span>
            <span>{{ __('Total Semanal') }}: <strong>{{ intdiv($grandTotalMinutes, 60) }}h {{ $grandTotalMinutes % 60 }}m</strong></span>
            <span>{{ __('Prom. Diario') }}: <strong>{{ intdiv($avgDaily, 60) }}h {{ $avgDaily % 60 }}m</strong></span>
        </div>
        <flux:button wire:click="$set('showBulkAssignModal', true)" variant="primary" icon="check" size="sm">
            {{ __('Guardar Horario Semanal') }}
        </flux:button>
    </div>
</div>