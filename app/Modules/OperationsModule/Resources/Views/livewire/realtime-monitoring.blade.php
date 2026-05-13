<div wire:poll.10s class="space-y-6">
    {{-- Header con Estadísticas --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Monitoreo en Tiempo Real</flux:heading>
            <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400 mt-1">
                @if($stats['worker_active'])
                    <div class="relative flex h-2 w-2">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </div>
                    <flux:subheading>Sincronización Activa • Actualizado {{ $stats['worker_last_update'] }}
                    </flux:subheading>
                @else
                    <div class="relative flex h-2 w-2">
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                    </div>
                    <flux:subheading class="text-red-500 dark:text-red-400 font-medium">Sincronización Retrasada / Worker
                        Caído • Último dato {{ $stats['worker_last_update'] }}</flux:subheading>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-12 gap-3">
            <flux:card class="flex flex-col items-center justify-center min-w-[100px] p-3 shadow-sm">
                <flux:text size="xs" class="uppercase font-bold tracking-wider text-zinc-500">Total</flux:text>
                <flux:heading size="xl" class="font-black">{{ $stats['total'] }}</flux:heading>
            </flux:card>

            <flux:card
                class="bg-green-50 dark:bg-green-900/10 flex flex-col items-center justify-center min-w-[100px] p-3 shadow-sm border-green-100 dark:border-green-800/30">
                <flux:text size="xs" class="uppercase font-bold tracking-wider text-green-600 dark:text-green-400">
                    Listos</flux:text>
                <flux:heading size="xl" class="font-black text-green-700 dark:text-green-500">{{ $stats['ready'] }}
                </flux:heading>
            </flux:card>

            <flux:card
                class="bg-blue-50 dark:bg-blue-900/10 flex flex-col items-center justify-center min-w-[100px] p-3 shadow-sm border-blue-100 dark:border-blue-800/30">
                <flux:text size="xs" class="uppercase font-bold tracking-wider text-blue-600 dark:text-blue-400">En
                    Llamada</flux:text>
                <flux:heading size="xl" class="font-black text-blue-700 dark:text-blue-500">{{ $stats['talking'] }}
                </flux:heading>
            </flux:card>

            <flux:card
                class="bg-amber-50 dark:bg-amber-900/10 flex flex-col items-center justify-center min-w-[100px] p-3 shadow-sm border-amber-100 dark:border-amber-800/30">
                <flux:text size="xs" class="uppercase font-bold tracking-wider text-amber-600 dark:text-amber-400">No
                    Listos</flux:text>
                <flux:heading size="xl" class="font-black text-amber-700 dark:text-amber-500">{{ $stats['not_ready'] }}
                </flux:heading>
            </flux:card>

            <flux:card
                class="bg-rose-50 dark:bg-rose-900/10 flex flex-col items-center justify-center min-w-[100px] p-3 shadow-sm border-rose-100 dark:border-rose-800/30">
                <flux:text size="xs" class="uppercase font-bold tracking-wider text-rose-600 dark:text-rose-400">
                    Ausentes</flux:text>
                <flux:heading size="xl" class="font-black text-rose-700 dark:text-rose-500">{{ $stats['absent_count'] }}
                </flux:heading>
            </flux:card>

            <flux:card
                class="bg-purple-50 dark:bg-purple-900/10 flex flex-col items-center justify-center min-w-[100px] p-3 shadow-sm border-purple-100 dark:border-purple-800/30">
                <flux:text size="xs" class="uppercase font-bold tracking-wider text-purple-600 dark:text-purple-400">
                    Desconec.</flux:text>
                <flux:heading size="xl" class="font-black text-purple-700 dark:text-purple-500">
                    {{ $stats['disconnected_count'] }}
                </flux:heading>
            </flux:card>

            <flux:card
                class="{{ $stats['adherence_percent'] < 85 ? 'bg-red-50 dark:bg-red-900/10 border-red-100 dark:border-red-800/30' : 'bg-indigo-50 dark:bg-indigo-900/10 border-indigo-100 dark:border-indigo-800/30' }} flex flex-col items-center justify-center min-w-[100px] p-3 shadow-sm border-2">
                <flux:text size="xs"
                    class="uppercase font-bold tracking-wider {{ $stats['adherence_percent'] < 85 ? 'text-red-600' : 'text-indigo-600' }}">
                    Adherencia</flux:text>
                <flux:heading size="xl"
                    class="font-black {{ $stats['adherence_percent'] < 85 ? 'text-red-700' : 'text-indigo-700' }}">
                    {{ $stats['adherence_percent'] }}%
                </flux:heading>
            </flux:card>
        </div>
    </div>

    {{-- Filtros y Tabla --}}
    <flux:card>
        <div class="space-y-6">
            {{-- Barra de herramientas superior --}}
            <div class="flex flex-col md:flex-row gap-3">
                <div class="flex-grow">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o usuario..."
                        icon="magnifying-glass" size="sm" />
                </div>

                <div class="flex flex-col md:flex-row gap-3">
                    <flux:select wire:model.live="teamId" size="sm" class="md:w-48">
                        <option value="">Todos los equipos</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="positionFilter" size="sm" class="md:w-48">
                        <option value="">Todos los cargos</option>
                        @foreach($positions as $position)
                            <option value="{{ $position->id }}">{{ $position->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="queueFilter" size="sm" class="md:w-48">
                        <option value="">Todas las colas</option>
                        @foreach($queues as $queue)
                            <option value="{{ $queue->name }}">{{ $queue->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="statusFilter" size="sm" class="md:w-48">
                        <option value="">Todos los estados</option>
                        <option value="READY">READY</option>
                        <option value="NOT_READY">NOT_READY</option>
                        <option value="TALKING">TALKING</option>
                        <option value="WORK">WORK</option>
                        <option value="RESERVED">RESERVED</option>
                        <option value="LOGOUT">LOGOUT</option>
                    </flux:select>

                    <flux:select wire:model.live="reasonFilter" size="sm" class="md:w-48">
                        <option value="">Todos los motivos</option>
                        @foreach($reasons as $reason)
                            <option value="{{ $reason }}">{{ $reason }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="expectedStateFilter" size="sm" class="md:w-48">
                        <option value="">Todos los turnos</option>
                        @foreach($expectedStateOptions as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:button wire:click="clearFilters" size="sm" variant="filled" icon="x-mark">
                        Limpiar Filtros
                    </flux:button>
                </div>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortField === 'agent'" :direction="$sortDirection"
                        wire:click="sortBy('agent')">Agente</flux:table.column>

                    <flux:table.column>Estado Programado</flux:table.column>
                    <flux:table.column sortable :sorted="$sortField === 'state'" :direction="$sortDirection"
                        wire:click="sortBy('state')">Estado Real</flux:table.column>
                    <flux:table.column>Alertas Operativas</flux:table.column>
                    <flux:table.column sortable :sorted="$sortField === 'duration'" :direction="$sortDirection"
                        wire:click="sortBy('duration')" align="end">Duración</flux:table.column>
                    <flux:table.column align="end">Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($agents as $agent)
                        @php
                            $color = $agent->color_hex ?? match ($agent->current_state) {
                                'READY' => 'green',
                                'TALKING' => 'blue',
                                'NOT_READY' => 'amber',
                                'RESERVED' => 'purple',
                                'WORK' => 'orange',
                                'LOGOUT', 'OFFLINE' => 'zinc',
                                default => 'zinc'
                            };
                            $displayName = $agent->display_name ?? $agent->current_state;
                            $duration = $agent->current_duration;

                            $isLogoutOrOffline = in_array($agent->current_state, ['LOGOUT', 'LOGGED_OUT', 'OFFLINE', 'UNKNOWN']);

                            if ($isLogoutOrOffline) {
                                $durationFormatted = '-';
                            } else {
                                $durationFormatted = sprintf('%02d:%02d:%02d', floor($duration / 3600), floor(($duration % 3600) / 60), $duration % 60);
                            }
                        @endphp
                        <flux:table.row :key="$agent->id"
                            class="group {{ !$agent->is_adherent ? 'bg-red-50/30 dark:bg-red-950/10' : '' }}">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <flux:avatar :name="$agent->first_name . ' ' . $agent->last_name" size="sm"
                                            class="ring-2 ring-zinc-100 dark:ring-zinc-800" />
                                        <div class="absolute -bottom-1 -right-1">
                                            @if($agent->is_adherent)
                                                <div
                                                    class="flex items-center justify-center w-4 h-4 rounded-full bg-white dark:bg-zinc-900 shadow-sm">
                                                    <flux:icon icon="check-circle" variant="micro" class="text-green-500" />
                                                </div>
                                            @else
                                                <div
                                                    class="flex items-center justify-center w-4 h-4 rounded-full bg-white dark:bg-zinc-900 shadow-sm">
                                                    <flux:icon icon="exclamation-circle" variant="micro" class="text-red-500" />
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-sm text-zinc-900 dark:text-white leading-tight">
                                            {{ $agent->first_name }} {{ $agent->last_name }}
                                        </span>
                                        <span
                                            class="text-[10px] font-medium text-zinc-500 uppercase tracking-tighter">{{ $agent->position_name ?? 'Sin cargo' }}
                                            • {{ $agent->team_name ?? 'Sin equipo' }}</span>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if(isset($agent->expected_state))
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full"
                                                style="background-color: {{ $agent->expected_state['color'] }}"></div>
                                            <span class="text-sm font-medium">{{ $agent->expected_state['label'] }}</span>
                                        </div>
                                        @if(isset($agent->expected_state['start_time']) && isset($agent->expected_state['end_time']))
                                            <div class="ml-4 leading-none">
                                                <flux:text size="xs" class="text-zinc-400 dark:text-zinc-500 font-mono">
                                                    {{ \Carbon\Carbon::parse($agent->expected_state['start_time'])->format('H:i') }}
                                                    - {{ \Carbon\Carbon::parse($agent->expected_state['end_time'])->format('H:i') }}
                                                </flux:text>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex flex-col gap-1">
                                    <flux:badge :color="$color" size="sm" class="font-bold w-fit">
                                        {{ $displayName }}
                                    </flux:badge>
                                    @if($agent->current_queue && $agent->current_state === 'TALKING')
                                        <span
                                            class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase truncate max-w-[120px]">
                                            <flux:icon icon="phone" variant="micro" class="mr-1 inline" />
                                            {{ $agent->current_queue }}
                                        </span>
                                    @elseif($agent->reason_code && !in_array($agent->current_state, ['READY', 'TALKING']))
                                        <span class="text-[10px] text-zinc-500 italic truncate max-w-[120px]">
                                            {{ $agent->reason_code }}
                                        </span>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1 flex-wrap">
                                    @foreach($agent->alerts as $alert)
                                        <flux:tooltip :content="$alert['label']">
                                            <flux:badge :variant="$alert['level'] === 'critical' ? 'solid' : 'outline'"
                                                :color="$alert['level'] === 'critical' ? 'red' : 'amber'" size="sm"
                                                class="cursor-help">
                                                <flux:icon :icon="match($alert['type']) {
                                                                                                'LATE_LOGIN' => 'clock',
                                                                                                'NO_SHOW' => 'x-circle',
                                                                                                'DISCONNECTED' => 'no-symbol',
                                                                                                'PERSONAL_TIME' => 'user-minus',
                                                                                                'AHT' => 'phone-arrow-up-right',
                                                                                                'ADHERENCE' => 'exclamation-triangle',
                                                                                                default => 'information-circle'
                                                                                            }" variant="micro" class="mr-1" />
                                                {{ $alert['label'] }}
                                            </flux:badge>
                                        </flux:tooltip>
                                    @endforeach
                                </div>
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <div class="flex flex-col items-end">
                                    <span
                                        class="font-mono font-bold text-sm {{ count($agent->alerts) > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }} tabular-nums">
                                        {{ $durationFormatted }}
                                    </span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <flux:button.group>
                                    <flux:button variant="filled" size="sm" icon="eye"
                                        wire:click="showDetails({{ $agent->emp_id }})" />

                                    @if($currentWeekId)
                                        <flux:button variant="filled" size="sm" icon="calendar"
                                            href="{{ route('schedules.planning.employee', ['week' => $currentWeekId, 'employee' => $agent->emp_id]) }}"
                                            wire:navigate />
                                    @endif
                                </flux:button.group>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-20">
                                <div class="flex flex-col items-center">
                                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-full mb-4">
                                        <flux:icon icon="users" class="size-10 text-zinc-300 dark:text-zinc-700" />
                                    </div>
                                    <flux:heading>Sin agentes activos</flux:heading>
                                    <flux:text class="text-sm">No se encontraron agentes que coincidan con los filtros
                                        aplicados.</flux:text>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if($agents->hasPages())
                <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    {{ $agents->links() }}
                </div>
            @endif
        </div>
    </flux:card>

    {{-- Modal de Detalles del Agente --}}
    <flux:modal name="agent-details-modal" class="md:w-[600px] space-y-6">
        @if($selectedAgent)
            <div class="flex items-center gap-4">
                <flux:avatar :name="$selectedAgent->first_name . ' ' . $selectedAgent->last_name" size="lg" />
                <div>
                    <flux:heading size="lg">{{ $selectedAgent->first_name }} {{ $selectedAgent->last_name }}</flux:heading>
                    <flux:subheading>{{ $selectedAgent->position_name }} • {{ $selectedAgent->team_name }}</flux:subheading>
                </div>
            </div>

            <flux:separator />

            <div class="grid grid-cols-2 gap-4">
                <flux:card class="p-3 bg-zinc-50 dark:bg-zinc-900/50">
                    <flux:text size="xs" class="uppercase font-bold text-zinc-500">Estado Actual</flux:text>
                    <div class="flex items-center gap-2 mt-1">
                        <flux:badge size="sm">{{ $selectedAgent->current_state }}</flux:badge>
                        @if($selectedAgent->reason_code)
                            <flux:text size="sm" class="italic">({{ $selectedAgent->reason_code }})</flux:text>
                        @endif
                    </div>
                </flux:card>
                <flux:card class="p-3 bg-zinc-50 dark:bg-zinc-900/50">
                    <flux:text size="xs" class="uppercase font-bold text-zinc-500">Último Cambio</flux:text>
                    <flux:text size="sm" class="mt-1 font-mono">
                        {{ \Carbon\Carbon::parse($selectedAgent->last_changed_at)->format('H:i:s') }}
                        ({{ \Carbon\Carbon::parse($selectedAgent->last_changed_at)->diffForHumans() }})
                    </flux:text>
                </flux:card>
            </div>

            @php
                $expected = $selectedAgent->expected_state;
            @endphp

            <flux:card class="p-4 border-l-4" style="border-left-color: {{ $expected['color'] ?? '#6b7280' }}">
                <div class="flex justify-between items-start">
                    <div>
                        <flux:text size="xs" class="uppercase font-bold text-zinc-500">Expectativa de Turno</flux:text>
                        <flux:heading size="sm" class="mt-1">{{ $expected['label'] }}</flux:heading>
                    </div>
                    @if(isset($expected['start_time']) && isset($expected['end_time']))
                        <div class="text-right">
                            <flux:text size="xs" class="uppercase font-bold text-zinc-500">Horario</flux:text>
                            <flux:text size="sm" font="mono" class="block mt-1">
                                {{ \Carbon\Carbon::parse($expected['start_time'])->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($expected['end_time'])->format('H:i') }}
                            </flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            <div class="space-y-4">
                <flux:heading size="sm">Línea de Tiempo del Día (Últimos Eventos)</flux:heading>
                <div class="space-y-2 max-h-[300px] overflow-y-auto pr-2">
                    @forelse($agentEvents as $event)
                        <div
                            class="flex items-center justify-between p-2 rounded-lg border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-zinc-300 dark:bg-zinc-600"></div>
                                <div class="flex flex-col">
                                    <flux:text size="sm" font="bold">{{ $event->agent_state }}</flux:text>
                                    <flux:text size="xs" class="text-zinc-500">{{ $event->reason_code ?? 'Sin motivo' }}
                                    </flux:text>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <flux:text size="xs" font="mono">
                                    {{ \Carbon\Carbon::parse($event->transition_time)->format('H:i:s') }}
                                </flux:text>
                                @if($event->duration)
                                    <flux:text size="xs" class="text-zinc-400">
                                        {{ sprintf('%02d:%02d', floor($event->duration / 60), $event->duration % 60) }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>
                    @empty
                        <flux:text size="sm" class="text-center py-4 text-zinc-500">No hay eventos registrados para hoy.
                        </flux:text>
                    @endforelse
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button x-on:click="$dispatch('modal-close', { name: 'agent-details-modal' })">Cerrar</flux:button>
                @if($currentWeekId)
                    <flux:button variant="primary" icon="calendar"
                        href="{{ route('schedules.planning.employee', ['week' => $currentWeekId, 'employee' => $selectedAgent->employee_id]) }}"
                        wire:navigate>
                        Ver Horario Completo
                    </flux:button>
                @endif
            </div>
        @endif
    </flux:modal>
</div>