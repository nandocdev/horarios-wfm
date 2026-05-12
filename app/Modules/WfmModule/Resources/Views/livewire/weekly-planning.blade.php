<div class="p-6">
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">{{ __('Planificación Semanal') }}</flux:heading>
            <flux:subheading class="mt-2">{{ __('Listado de semanas de planificación de horarios.') }}</flux:subheading>
        </div>
        @can('schedules.manage')
        <flux:button wire:click="confirmCreateWeek" icon="plus" variant="primary">
            {{ __('Nueva Semana') }}
        </flux:button>
        @endcan
    </div>

    <!-- Modal de Confirmación de Nueva Semana -->
    <flux:modal wire:model="showCreateModal" class="min-w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Confirmar Nueva Semana') }}</flux:heading>
                <flux:subheading>{{ __('Revise las fechas de planificación antes de crear la semana.') }}
                </flux:subheading>
            </div>

            @if($nextWeekStart && $nextWeekEnd)
                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-3">
                        <flux:icon icon="calendar-days" class="text-blue-500" />
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold">{{ __('Periodo de Planificación') }}:</span>
                            <span class="text-sm">
                                {{ \Carbon\Carbon::parse($nextWeekStart)->format('d M, Y') }}
                                {{ __('al') }}
                                {{ \Carbon\Carbon::parse($nextWeekEnd)->format('d M, Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">{{ __('Cancelar') }}
                </flux:button>
                <flux:button wire:click="createNextWeek" variant="primary" icon="check">
                    {{ __('Crear Semana') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:table :paginate="$weeks">
        <flux:table.columns>
            <flux:table.column>{{ __('Semana') }}</flux:table.column>
            <flux:table.column>{{ __('Estado') }}</flux:table.column>
            <flux:table.column>{{ __('Equipos Planificados') }}</flux:table.column>
            <flux:table.column>{{ __('Publicación') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Acciones') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($weeks as $week)
                <flux:table.row :key="$week->id">
                    <flux:table.cell class="font-medium">
                        <div class="flex flex-col">
                            <span>{{ $week->week_start_date->format('d M') }} -
                                {{ $week->week_end_date->format('d M, Y') }}</span>
                            <span class="text-xs text-zinc-500">Semana {{ $week->week_start_date->weekOfYear }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="match($week->status) {
                                                                                                                            'draft' => 'zinc',
                                                                                                                            'published' => 'green',
                                                                                                                            'closed' => 'amber',
                                                                                                                            default => 'zinc'
                                                                                                                        }">
                            {{ ucfirst($week->status) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:icon icon="users" size="xs" class="text-zinc-400" />
                            <span>{{ $week->team_assignments_count }} equipos</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($week->status === 'draft')
                            @can('schedules.manage')
                                <flux:button wire:click="publishWeek({{ $week->id }})" variant="primary" size="sm"
                                    icon="arrow-up-tray">
                                    {{ __('Publicar') }}
                                </flux:button>
                            @else
                                <flux:badge color="zinc" icon="clock">
                                    {{ __('En Preparación') }}
                                </flux:badge>
                            @endcan
                        @else
                            <flux:badge color="green" icon="check-circle">
                                {{ __('Publicado') }}
                            </flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button href="{{ route('schedules.planning.teams', ['week' => $week->id]) }}" variant="primary"
                            color="emerald" size="sm" icon="pencil-square" wire:navigate>
                            {{ __('Gestionar Equipos') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>