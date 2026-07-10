<div class="p-6 lg:p-8 space-y-8 flex-1 flex flex-col">
    <div class="flex items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <flux:button href="{{ route('schedules.planning') }}" icon="arrow-left" variant="ghost" wire:navigate />
            <div>
                <flux:heading size="xl">{{ __('Gestión de Equipos') }}</flux:heading>
                <flux:subheading class="mt-2">
                    {{ __('Semana del') }} {{ $week->week_start_date->format('d M') }} {{ __('al') }}
                    {{ $week->week_end_date->format('d M, Y') }}
                </flux:subheading>
            </div>
        </div>
        <flux:button href="{{ route('schedules.planning.import', ['week' => $week->id]) }}" variant="primary" icon="document-arrow-up" wire:navigate>
            {{ __('Importar Horario') }}
        </flux:button>

       
    </div>

    <flux:table>
        <flux:table.columns class="sticky top-0 z-10 bg-white">
            <flux:table.column>{{ __('Equipo') }}</flux:table.column>
            <flux:table.column>{{ __('Turno Base') }}</flux:table.column>
            <flux:table.column>{{ __('Entrada') }}</flux:table.column>
            <flux:table.column>{{ __('Salida') }}</flux:table.column>
            <flux:table.column>{{ __('Lunch') }}</flux:table.column>
            <flux:table.column>{{ __('Break') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Acciones') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($teams as $team)
                <flux:table.row :key="$team->id" class="hover:bg-slate-50">
                    <flux:table.cell class="py-1">
                        <div class="flex flex-col">
                            <span class="font-medium">{{ $team->name }}</span>
                            <span class="text-xs text-slate-500">{{ $team->supervisor?->full_name }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="py-1">
                        <flux:select wire:model.live="teamSchedules.{{ $team->id }}"
                            placeholder="{{ __('Seleccionar turno...') }}">
                            <flux:select.option selected readonly value="">{{ __('Sin asignar') }}</flux:select.option>
                            @foreach($schedules as $sched)
                                <flux:select.option value="{{ $sched->id }}">
                                    {{ $sched->name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:table.cell>
                    <flux:table.cell class="py-1">
                        <flux:input wire:model="teamStart.{{ $team->id }}" type="time" size="sm" icon="clock" />
                    </flux:table.cell>
                    <flux:table.cell class="py-1">
                        <flux:input wire:model="teamEnd.{{ $team->id }}" type="time" size="sm" icon="clock" />
                    </flux:table.cell>
                    <flux:table.cell class="py-1">
                        <flux:input wire:model="teamLunch.{{ $team->id }}" type="time" size="sm" icon="clock" />
                    </flux:table.cell>
                    <flux:table.cell class="py-1">
                        <flux:input wire:model="teamBreak.{{ $team->id }}" type="time" size="sm" icon="clock" />
                    </flux:table.cell>
                    <flux:table.cell align="end" class="py-1">
                        <div class="flex justify-end gap-2">
                            <flux:button wire:click="assignToTeam({{ $team->id }})" variant="primary"
                                size="sm" icon="check">
                                {{ __('Asignar') }}
                            </flux:button>
                            <flux:button
                                href="{{ route('schedules.planning.team', ['week' => $week->id, 'team' => $team->id]) }}"
                                variant="subtle" size="sm" icon="eye" wire:navigate>
                                {{ __('Ver Detalle') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>