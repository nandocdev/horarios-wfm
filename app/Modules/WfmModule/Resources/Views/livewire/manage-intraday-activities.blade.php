<div class="p-6 space-y-8">

    {{-- CABECERA --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">Actividades Intradía</flux:heading>
            <flux:subheading class="mt-1">
                @if($isWfm)
                    Gestiona los periodos aprobados por equipo y supervisa las asignaciones.
                @else
                    Asigna a tus operadores en los periodos autorizados por WFM.
                @endif
            </flux:subheading>
        </div>
        <div class="flex items-center gap-3">
            <flux:input type="date" wire:model.live="date" class="w-44" />
            @if($isWfm)
                <flux:select wire:model.live="selectedTeamId" placeholder="Todos los equipos" class="w-48">
                    @foreach($teams as $team)
                        <flux:select.option :value="$team->id">{{ $team->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:button wire:click="openPeriodModal()" variant="primary" icon="plus">
                    Nuevo Periodo
                </flux:button>
            @endif
        </div>
    </div>

    {{-- =====================================================
         SECCIÓN 1: PERIODOS APROBADOS
         ===================================================== --}}
    <div>
        <flux:heading size="lg" class="mb-4">
            <flux:icon name="clock" class="inline-block w-5 h-5 mr-1 text-indigo-500" />
            Periodos Aprobados
        </flux:heading>

        @if($periods->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700 p-10 text-center">
                <flux:icon name="no-symbol" class="mx-auto w-10 h-10 text-zinc-300 dark:text-zinc-600 mb-3" />
                <flux:text class="text-zinc-500">
                    {{ $isWfm ? 'No hay periodos aprobados para esta fecha. Crea uno con el botón superior.' : 'No hay periodos aprobados para tu equipo en esta fecha.' }}
                </flux:text>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($periods as $period)
                    @php
                        $used = $period->assignments_count;
                        $max = $period->max_slots;
                        $pct = $max > 0 ? round(($used / $max) * 100) : 0;
                        $isFull = $used >= $max;
                        $barColor = $isFull ? 'bg-red-500' : ($pct >= 70 ? 'bg-amber-400' : 'bg-emerald-500');
                    @endphp
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition-shadow p-5 space-y-4">

                        {{-- Badge actividad --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="space-y-1">
                                <flux:badge :color="$isFull ? 'red' : 'indigo'" size="sm">
                                    {{ $period->activityDefinition->name }}
                                </flux:badge>
                                <div class="flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                    <flux:icon name="users" class="w-4 h-4" />
                                    {{ $period->team->name ?? '—' }}
                                </div>
                            </div>
                            @if($isWfm)
                                <div class="flex gap-1">
                                    <flux:button wire:click="openPeriodModal({{ $period->id }})" variant="ghost" size="xs" icon="pencil" />
                                    <flux:button wire:click="deletePeriod({{ $period->id }})" variant="ghost" size="xs" icon="trash"
                                        wire:confirm="¿Eliminar este periodo? Se perderán las asignaciones vinculadas." />
                                </div>
                            @endif
                        </div>

                        {{-- Horario --}}
                        <div class="flex items-center gap-2 text-sm font-medium">
                            <flux:icon name="clock" class="w-4 h-4 text-zinc-400" />
                            <span>{{ $period->start_time }} – {{ $period->end_time }}</span>
                        </div>

                        {{-- Barra de capacidad --}}
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs text-zinc-500">
                                <span>Slots</span>
                                <span class="{{ $isFull ? 'text-red-500 font-semibold' : '' }}">{{ $used }} / {{ $max }}</span>
                            </div>
                            <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2">
                                <div class="{{ $barColor }} h-2 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>

                        {{-- Notas --}}
                        @if($period->notes)
                            <p class="text-xs text-zinc-400 italic truncate">{{ $period->notes }}</p>
                        @endif

                        {{-- Botón asignar --}}
                        @if(auth()->user()->can('wfm.intraday.assign') && !$isFull)
                            <flux:button wire:click="openAssignmentModal({{ $period->id }})" variant="filled" class="w-full" size="sm" icon="user-plus">
                                Asignar Operador
                            </flux:button>
                        @elseif($isFull)
                            <div class="text-center text-xs text-red-500 font-medium py-1">
                                <flux:icon name="exclamation-triangle" class="inline w-3 h-3 mr-1" /> Capacidad máxima alcanzada
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- =====================================================
         SECCIÓN 2: ACTIVIDADES YA ASIGNADAS
         ===================================================== --}}
    <div>
        <flux:heading size="lg" class="mb-4">
            <flux:icon name="queue-list" class="inline-block w-5 h-5 mr-1 text-violet-500" />
            Actividades del Día
        </flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Empleado</flux:table.column>
                <flux:table.column>Equipo</flux:table.column>
                <flux:table.column>Actividad</flux:table.column>
                <flux:table.column>Horario</flux:table.column>
                <flux:table.column>Periodo Ref.</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($activities as $act)
                    @php
                        $rangeStart = $act->getRangeStart();
                        $rangeEnd   = $act->getRangeEnd();
                    @endphp
                    <flux:table.row :key="$act->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-xs font-bold text-indigo-700 dark:text-indigo-300">
                                    {{ strtoupper(substr($act->employee->first_name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-sm">{{ $act->employee->full_name ?? '—' }}</p>
                                    <p class="text-xs text-zinc-400">{{ $act->employee->username ?? '' }}</p>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text size="sm">{{ $act->employee->team->name ?? '—' }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$act->activityType->color ?? 'zinc'">
                                {{ $act->activityType->name ?? '—' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="font-mono text-sm">
                                {{ $rangeStart?->format('H:i') ?? '—' }} – {{ $rangeEnd?->format('H:i') ?? '—' }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($act->approvedPeriod)
                                <flux:badge size="sm" color="blue" icon="check-circle">
                                    Autorizado
                                </flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Directo</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button wire:click="deleteActivity({{ $act->id }})"
                                variant="ghost" size="sm" icon="trash"
                                wire:confirm="¿Eliminar esta actividad?" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-8 text-zinc-400">
                            No hay actividades asignadas en esta fecha.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- =====================================================
         MODAL: CREAR / EDITAR PERIODO APROBADO (WFM)
         ===================================================== --}}
    @if($isWfm)
    <flux:modal wire:model="showPeriodModal" class="w-full max-w-lg">
        <form wire:submit="savePeriod" class="space-y-5">
            <flux:heading size="lg">
                {{ $periodId ? 'Editar Periodo Aprobado' : 'Nuevo Periodo Aprobado' }}
            </flux:heading>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="periodTeamId" label="Equipo" placeholder="Seleccione..." class="col-span-2">
                    @foreach($teams as $team)
                        <flux:select.option :value="$team->id">{{ $team->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="periodActivityDefinitionId" label="Actividad" placeholder="Seleccione..." class="col-span-2">
                    @foreach($definitions as $def)
                        <flux:select.option :value="$def->id">{{ $def->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input type="date" wire:model="periodDate" label="Fecha" />
                <flux:input type="number" wire:model="periodMaxSlots" label="Máx. Slots" min="1" max="100" />

                <flux:input type="time" wire:model="periodStartTime" label="Hora inicio" />
                <flux:input type="time" wire:model="periodEndTime" label="Hora fin" />
            </div>

            <flux:input wire:model="periodNotes" label="Notas (opcional)" placeholder="Indicaciones adicionales..." />

            <div class="flex justify-end gap-3 pt-2">
                <flux:button wire:click="$set('showPeriodModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Guardar Periodo</flux:button>
            </div>
        </form>
    </flux:modal>
    @endif

    {{-- =====================================================
         MODAL: ASIGNAR OPERADOR A PERIODO
         ===================================================== --}}
    <flux:modal wire:model="showAssignmentModal" class="w-full max-w-lg">
        <form wire:submit="assignActivity" class="space-y-5">
            <flux:heading size="lg">Asignar Operadores al Periodo</flux:heading>

            <flux:fieldset>
                <flux:legend>Selecciona operadores disponibles</flux:legend>
                <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                    @forelse($availableEmployees as $emp)
                        <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer">
                            <flux:checkbox
                                wire:model="selectedEmployeeIds"
                                value="{{ $emp->id }}"
                            />
                            <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-xs font-bold text-indigo-700 dark:text-indigo-300 shrink-0">
                                {{ strtoupper(substr($emp->first_name ?? '?', 0, 1)) }}
                            </div>
                            <div class="text-sm">
                                <p class="font-medium">{{ $emp->first_name }} {{ $emp->last_name }}</p>
                                <p class="text-xs text-zinc-400">{{ $emp->username }}</p>
                            </div>
                        </label>
                    @empty
                        <p class="text-sm text-zinc-400 italic p-2">No hay operadores disponibles para este periodo.</p>
                    @endforelse
                </div>
                <flux:error name="selectedEmployeeIds" />
            </flux:fieldset>

            <div class="grid grid-cols-2 gap-4">
                <flux:input type="time" wire:model="startTime" label="Hora inicio" />
                <flux:input type="time" wire:model="endTime" label="Hora fin" />
            </div>

            <flux:input wire:model="assignNotes" label="Notas (opcional)" placeholder="Instrucciones adicionales..." />

            <div class="flex justify-end gap-3 pt-2">
                <flux:button wire:click="$set('showAssignmentModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button type="submit" variant="primary" icon="user-plus">Asignar</flux:button>
            </div>
        </form>
    </flux:modal>

</div>
