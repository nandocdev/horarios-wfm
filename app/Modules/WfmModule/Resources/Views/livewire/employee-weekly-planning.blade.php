<div class="p-6 lg:p-8  mx-auto flex-1 flex flex-col">
    <div data-tour="planning-header" class="flex items-center gap-4">
        <flux:button href="{{ route('schedules.planning.team', ['week' => $week->id, 'team' => $employee->team_id]) }}"
            icon="arrow-left" variant="ghost" wire:navigate />
        <div class="flex-1">
            <flux:heading size="xl">{{ __('Editar Horario Semanal') }}: {{ $employee->full_name }}</flux:heading>
            <flux:subheading class="mt-1">
                {{ __('Semana del') }} {{ $week->week_start_date->format('d M') }} {{ __('al') }}
                {{ $week->week_end_date->format('d M, Y') }}
            </flux:subheading>
        </div>
        <x-wfm.tour-button :tour="'wfm-planning'" />
    </div>

    <form wire:submit="save" class="mt-8 space-y-4">
        <div data-tour="planning-grid">
            <flux:table>
            <flux:table.columns class="sticky top-0 z-10 bg-white">
                <flux:table.column>{{ __('Día') }}</flux:table.column>
                <flux:table.column>{{ __('Turno') }}</flux:table.column>
                <flux:table.column>{{ __('Entrada') }}</flux:table.column>
                <flux:table.column>{{ __('Salida') }}</flux:table.column>
                <flux:table.column>{{ __('Inicio Almuerzo') }}</flux:table.column>
                <flux:table.column>{{ __('Inicio Descanso') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($days as $dayNum => $dayName)
                    @php
                        $dayException = $exceptionsByDay[$dayNum] ?? null;
                    @endphp
                    <flux:table.row :key="$dayNum"
                        class="hover:bg-slate-50 {{ $dayException ? 'bg-slate-50/50 dark:bg-slate-800/30' : '' }}">
                        <flux:table.cell class="py-1 font-medium">
                            <div class="flex flex-col">
                                <span>{{ $dayName }}</span>
                                @if($dayException)
                                    <span
                                        class="text-[10px] text-slate-500 font-medium italic">{{ __('Excepción Activa') }}</span>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="py-1">
                            @if($dayException)
                                <flux:badge :color="$dayException->reason?->color ?? 'slate'" size="sm"
                                    class="font-bold uppercase">
                                    {{ $dayException->reason?->name ?? __('EXCEPCIÓN') }}
                                </flux:badge>
                            @else
                                <flux:select wire:model.live="assignments.{{ $dayNum }}.schedule_id"
                                    placeholder="{{ __('Seleccionar turno...') }}">
                                    <flux:select.option value="">{{ __('LIBRE (OFF)') }}</flux:select.option>
                                    @foreach($activeSchedules as $sched)
                                        @if(in_array((int) $dayNum, $sched->allowed_days ?? []))
                                            <flux:select.option value="{{ $sched->id }}">
                                                {{ $sched->name }} ({{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }})
                                            </flux:select.option>
                                        @endif
                                    @endforeach
                                </flux:select>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="py-1">
                            <flux:input wire:model="assignments.{{ $dayNum }}.start_time" type="time" icon="clock"
                                :disabled="(bool)$dayException" />
                        </flux:table.cell>
                        <flux:table.cell class="py-1">
                            <flux:input wire:model="assignments.{{ $dayNum }}.end_time" type="time" icon="clock"
                                :disabled="(bool)$dayException" />
                        </flux:table.cell>
                        <flux:table.cell class="py-1">
                            <flux:input wire:model="assignments.{{ $dayNum }}.lunch_start_time" type="time" icon="clock"
                                :disabled="(bool)$dayException" />
                        </flux:table.cell>
                        <flux:table.cell class="py-1">
                            <flux:input wire:model="assignments.{{ $dayNum }}.break_start_time" type="time" icon="clock"
                                :disabled="(bool)$dayException" />
                        </flux:table.cell>
                </flux:table.row>
            @endforeach
            </flux:table.rows>
        </flux:table>
        </div>

        <div data-tour="planning-actions" class="flex justify-end gap-3">
            <flux:button
                href="{{ route('schedules.planning.team', ['week' => $week->id, 'team' => $employee->team_id]) }}"
                variant="ghost" wire:navigate>
                {{ __('Cancelar') }}
            </flux:button>
            <flux:button type="submit" variant="primary">
                {{ __('Guardar Horario Semanal') }}
            </flux:button>
        </div>
    </form>
</div>