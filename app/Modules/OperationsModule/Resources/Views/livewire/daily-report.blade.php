<div class="space-y-6">
    {{-- Header --}}
    <div data-tour="daily-report-header" class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Reporte Diario</flux:heading>
            <flux:subheading>{{ \Carbon\Carbon::parse($date)->locale('es')->translatedFormat('l d F Y') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <x-wfm.tour-button :tour="'operations.daily-report'" />
            <flux:button wire:click="previousDay" icon="chevron-left" size="sm" variant="subtle" />
            <flux:button wire:click="nextDay" icon="chevron-right" size="sm" variant="subtle" />
            <flux:button wire:click="$set('date', '{{ now()->toDateString() }}')" size="sm" variant="subtle">Hoy</flux:button>
        </div>
    </div>

    {{-- Toggle + Filters --}}
    <div data-tour="daily-report-view-toggle" class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-lg">
            <button wire:click="switchView('operator')"
                    class="px-4 py-2 text-sm font-medium rounded-md transition-colors duration-150
                    {{ $view === 'operator' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                <flux:icon name="user" class="inline w-4 h-4 -mt-0.5 mr-1" />Mi Reporte
            </button>
            <button wire:click="switchView('coordinator')"
                    class="px-4 py-2 text-sm font-medium rounded-md transition-colors duration-150
                    {{ $view === 'coordinator' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                <flux:icon name="users" class="inline w-4 h-4 -mt-0.5 mr-1" />Equipo
            </button>
        </div>
        @if($view === 'coordinator' && $teams->isNotEmpty())
            <flux:select wire:model.live="teamId" placeholder="Todos los equipos" class="w-48">
                @foreach($teams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
            </flux:select>
        @endif
    </div>

    @if($view === 'operator' && $reportData)
        @php $d = $reportData; @endphp
        {{-- Operator View --}}
        {{-- Row 1: Cards de horario --}}
        <div data-tour="daily-report-content" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <flux:icon name="arrow-right-circle" class="w-4 h-4 text-blue-500" />
                        <flux:heading size="sm" class="text-blue-700 dark:text-blue-300">Entrada</flux:heading>
                    </div>
                    <flux:separator />
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <flux:text size="xs" class="text-slate-500">Programado</flux:text>
                            <flux:text size="lg" class="font-bold block">{{ $d['scheduled_entry'] }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="xs" class="text-slate-500">Real</flux:text>
                            <flux:text size="lg" class="font-bold block {{ $d['realtime_entry'] ? 'text-green-600' : 'text-slate-400' }}">
                                {{ $d['realtime_entry'] ?? '--:--' }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <flux:icon name="clock" class="w-4 h-4 text-amber-500" />
                        <flux:heading size="sm" class="text-amber-700 dark:text-amber-300">Almuerzo</flux:heading>
                    </div>
                    <flux:separator />
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <flux:text size="xs" class="text-slate-500">Programado</flux:text>
                            <flux:text size="sm" class="font-bold block">{{ $d['scheduled_lunch'] ?? '--:--' }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="xs" class="text-slate-500">Duración real</flux:text>
                            <flux:text size="sm" class="font-bold block {{ $d['realtime_lunch_seconds'] > 0 ? 'text-green-600' : 'text-slate-400' }}">
                                {{ $d['realtime_lunch_seconds'] > 0 ? gmdate('H:i:s', $d['realtime_lunch_seconds']) : '--:--' }}
                            </flux:text>
                        </div>
                    </div>
                    @if($d['lunch_duration'] > 0)
                        <flux:text size="xs" class="text-slate-400">Duración programada: {{ $d['lunch_duration'] }} min</flux:text>
                    @endif
                </div>
            </flux:card>

            <flux:card>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <flux:icon name="clock" class="w-4 h-4 text-emerald-500" />
                        <flux:heading size="sm" class="text-emerald-700 dark:text-emerald-300">Descanso</flux:heading>
                    </div>
                    <flux:separator />
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <flux:text size="xs" class="text-slate-500">Programado</flux:text>
                            <flux:text size="sm" class="font-bold block">{{ $d['scheduled_break'] ?? '--:--' }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="xs" class="text-slate-500">Duración real</flux:text>
                            <flux:text size="sm" class="font-bold block {{ $d['realtime_break_seconds'] > 0 ? 'text-green-600' : 'text-slate-400' }}">
                                {{ $d['realtime_break_seconds'] > 0 ? gmdate('H:i:s', $d['realtime_break_seconds']) : '--:--' }}
                            </flux:text>
                        </div>
                    </div>
                    @if($d['break_duration'] > 0)
                        <flux:text size="xs" class="text-slate-400">Duración programada: {{ $d['break_duration'] }} min</flux:text>
                    @endif
                </div>
            </flux:card>
        </div>

        {{-- Row 2: Llamadas + Actividades + Desconexiones --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Llamadas por Cola --}}
            <flux:card>
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <flux:icon name="phone" class="w-4 h-4 text-indigo-500" />
                        <flux:heading size="sm">Llamadas Atendidas por Cola</flux:heading>
                    </div>
                    <flux:separator />
                    @if(!empty($d['queue_performance']))
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-slate-500 uppercase">
                                        <th class="pb-1 font-semibold">Cola</th>
                                        <th class="pb-1 font-semibold text-right">Llamadas</th>
                                        <th class="pb-1 font-semibold text-right">AHT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($d['queue_performance'] as $qp)
                                        <tr class="border-t border-slate-100 dark:border-slate-800">
                                            <td class="py-1.5 font-medium">{{ $qp->queue_name ?? '—' }}</td>
                                            <td class="py-1.5 text-right">{{ $qp->total_offered ?? 0 }}</td>
                                            <td class="py-1.5 text-right font-mono">
                                                {{ $qp->avg_aht ? gmdate('i:s', (int) $qp->avg_aht) : '--:--' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <flux:text class="text-slate-400 text-center py-4">Sin datos de llamadas para esta fecha</flux:text>
                    @endif
                    <div class="flex justify-between text-sm pt-2 border-t border-slate-100 dark:border-slate-800">
                        <flux:text size="sm" class="font-medium">Total</flux:text>
                        <flux:text size="sm" class="font-mono font-bold">{{ $d['calls_total'] }} llamadas</flux:text>
                    </div>
                </div>
            </flux:card>

            {{-- Actividades Intradía --}}
            <flux:card>
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <flux:icon name="clipboard-document-list" class="w-4 h-4 text-amber-500" />
                        <flux:heading size="sm">Actividades</flux:heading>
                    </div>
                    <flux:separator />
                    @if(!empty($d['intraday_events']))
                        <div class="space-y-2">
                            @foreach($d['intraday_events'] as $event)
                                <div class="flex items-center justify-between p-2 bg-slate-50 dark:bg-slate-900 rounded">
                                    <div>
                                        <flux:text size="sm" class="font-medium">{{ $event['title'] ?? 'Actividad' }}</flux:text>
                                        <flux:text size="xs" class="text-slate-400 block">{{ $event['time'] ?? '' }}</flux:text>
                                    </div>
                                    <flux:text size="xs" class="text-slate-500">{{ $event['detail'] ?? '' }}</flux:text>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <flux:text class="text-slate-400 text-center py-4">Sin actividades programadas</flux:text>
                    @endif
                </div>
            </flux:card>

            {{-- Desconexiones / Estados --}}
            <flux:card>
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <flux:icon name="signal-slash" class="w-4 h-4 text-red-500" />
                        <flux:heading size="sm">Desconexiones</flux:heading>
                    </div>
                    <flux:separator />
                    <div class="space-y-2">
                        @php
                            $states = [
                                ['label' => 'TALKING', 'seconds' => $d['talk_seconds'], 'class' => 'bg-green-500'],
                                ['label' => 'READY', 'seconds' => $d['ready_seconds'], 'class' => 'bg-blue-500'],
                                ['label' => 'NOT READY', 'seconds' => $d['not_ready_seconds'], 'class' => 'bg-red-500'],
                                ['label' => 'WORK', 'seconds' => $d['work_seconds'], 'class' => 'bg-amber-500'],
                            ];
                        @endphp
                        @foreach($states as $s)
                            <div class="flex items-center justify-between p-2 bg-slate-50 dark:bg-slate-900 rounded">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full {{ $s['class'] }}"></span>
                                    <flux:text size="sm">{{ $s['label'] }}</flux:text>
                                </div>
                                <flux:text size="sm" class="font-mono">{{ gmdate('H:i:s', $s['seconds']) }}</flux:text>
                            </div>
                        @endforeach
                    </div>
                    <flux:separator />
                    <div class="flex justify-between text-sm">
                        <flux:text size="sm" class="font-medium">Total conectado</flux:text>
                        <flux:text size="sm" class="font-mono font-bold">{{ gmdate('H:i:s', $d['logged_in_seconds']) }}</flux:text>
                    </div>
                    @if($d['exceptions'])
                        <flux:badge color="amber" size="sm" class="mt-2">Con excepción de horario</flux:badge>
                    @endif
                </div>
            </flux:card>
        </div>
    @elseif($view === 'coordinator')
        {{-- Coordinator View --}}
        <flux:card data-tour="daily-report-content" class="overflow-x-auto">
            @if($reportData->isEmpty())
                <div class="text-center py-12">
                    <flux:icon name="users" class="w-12 h-12 text-slate-200 mx-auto mb-3" />
                    <flux:text class="text-slate-400">No hay miembros del equipo para mostrar</flux:text>
                </div>
            @else
                <table class="w-full text-sm whitespace-nowrap">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 uppercase border-b border-slate-200 dark:border-slate-700">
                            <th class="pb-2 pr-3 font-semibold sticky left-0 bg-white dark:bg-zinc-900 z-10">Nombre</th>
                            <th class="pb-2 px-3 font-semibold">Equipo</th>
                            <th class="pb-2 px-3 font-semibold">Cargo</th>
                            <th class="pb-2 px-3 font-semibold text-center">Entrada</th>
                            <th class="pb-2 px-3 font-semibold text-center">Logged-in</th>
                            <th class="pb-2 px-3 font-semibold text-center">Almuerzo</th>
                            <th class="pb-2 px-3 font-semibold text-center">NR- Alm</th>
                            <th class="pb-2 px-3 font-semibold text-center">T.Acum</th>
                            <th class="pb-2 px-3 font-semibold text-center">Descanso</th>
                            <th class="pb-2 px-3 font-semibold text-center">NR- Desc</th>
                            <th class="pb-2 px-3 font-semibold text-center">T.Acum</th>
                            <th class="pb-2 px-3 font-semibold text-center">Llamadas</th>
                            <th class="pb-2 pl-3 font-semibold text-center">Actividades</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $d)
                            <tr class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="py-2 pr-3 sticky left-0 bg-white dark:bg-zinc-900 z-10">
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="xs" />
                                        <div>
                                            <flux:text size="sm" class="font-medium">{{ $d['full_name'] }}</flux:text>
                                            <flux:text size="xs" class="text-slate-400 block">{{ '@'.$d['username'] }}</flux:text>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2 px-3 text-slate-600">{{ $d['team_name'] ?? '—' }}</td>
                                <td class="py-2 px-3 text-slate-600">{{ $d['position_name'] ?? '—' }}</td>
                                <td class="py-2 px-3 text-center font-mono text-xs">{{ $d['scheduled_entry'] }}</td>
                                <td class="py-2 px-3 text-center">
                                    <span class="inline-block w-2 h-2 rounded-full {{ $d['current_state'] !== 'LOGOUT' && $d['current_state'] !== 'OFFLINE' ? 'bg-green-500' : 'bg-red-400' }}"></span>
                                </td>
                                <td class="py-2 px-3 text-center font-mono text-xs">{{ $d['scheduled_lunch'] ?? '—' }}</td>
                                <td class="py-2 px-3 text-center font-mono text-xs">
                                    {{ $d['realtime_lunch_seconds'] > 0 ? gmdate('H:i:s', $d['realtime_lunch_seconds']) : '—' }}
                                </td>
                                <td class="py-2 px-3 text-center font-mono text-xs text-slate-500">{{ gmdate('H:i', $d['logged_in_seconds']) }}</td>
                                <td class="py-2 px-3 text-center font-mono text-xs">{{ $d['scheduled_break'] ?? '—' }}</td>
                                <td class="py-2 px-3 text-center font-mono text-xs">
                                    {{ $d['realtime_break_seconds'] > 0 ? gmdate('H:i:s', $d['realtime_break_seconds']) : '—' }}
                                </td>
                                <td class="py-2 px-3 text-center font-mono text-xs text-slate-500">{{ gmdate('H:i', $d['logged_in_seconds']) }}</td>
                                <td class="py-2 px-3 text-center font-mono text-xs">{{ $d['calls_total'] }}</td>
                                <td class="py-2 pl-3 text-center">
                                    <flux:badge size="sm" color="{{ count($d['intraday_events']) > 0 ? 'indigo' : 'zinc' }}" inset="top">
                                        {{ count($d['intraday_events']) }}
                                    </flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-12 text-slate-400">Sin datos para mostrar</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </flux:card>
    @endif
</div>
