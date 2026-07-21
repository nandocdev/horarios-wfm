<div class="space-y-4">
    @php
        $isHistorical = $employeeData['is_historical'] ?? false;
        $selectedDate = \Carbon\Carbon::parse($selectedDate);
        $isToday = $selectedDate->isToday();
    @endphp

    <x-wfm.page-header :title="$employeeData['name'] ?? 'Mi Jornada'" :description="$employeeData['team'] ?? ''">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <flux:button wire:click="previousDay" variant="ghost" icon="chevron-left" size="xs" />
                <flux:text class="font-mono text-sm {{ $isToday ? 'text-wfm-info font-semibold' : 'text-wfm-surface-muted' }}">
                    {{ $selectedDate->locale('es')->translatedFormat('l d F Y') }}
                    @if($isToday)
                        <span class="text-[10px] bg-wfm-info/10 text-wfm-info px-1.5 py-0.5 rounded font-mono">Hoy</span>
                    @endif
                </flux:text>
                <flux:button wire:click="nextDay" variant="ghost" icon="chevron-right" size="xs" @if($isToday) disabled @endif />
            </div>
        </x-slot:actions>
    </x-wfm.page-header>

    @if(!$employeeData)
        <x-wfm.empty icon="user" message="Seleccione un empleado para ver su jornada" />
    @else
        @php $d = $employeeData; @endphp

        {{-- Estado actual: siempre lo primero que se lee --}}
        <div class="flex items-center justify-between p-3 rounded-lg border border-wfm-surface-border bg-white dark:bg-wfm-navy-900 shadow-sm">
            <div class="flex items-center gap-3">
                @php
                    $stateBadgeColor = match($d['current_state'] ?? 'OFFLINE') {
                        'READY' => 'green',
                        'TALKING' => 'blue',
                        'WORK', 'ACW' => 'purple',
                        'NOT_READY' => 'amber',
                        'ALMUERZO', 'LUNCH' => 'orange',
                        'DESCANSO', 'BREAK' => 'yellow',
                        default => 'red',
                    };
                @endphp
                <flux:badge :color="$stateBadgeColor" size="lg">{{ $d['current_state'] ?? 'OFFLINE' }}</flux:badge>
                @if($d['disconnected_with_activity'] ?? false)
                    <flux:badge color="red" icon="exclamation-triangle">Fuera de línea con actividad previa</flux:badge>
                @endif
            </div>
            @if(!$isHistorical)
                <flux:text size="sm" variant="subtle">
                    @if($d['real_entry'])
                        Conectado desde {{ $d['real_entry'] }}
                    @else
                        Sin registrar
                    @endif
                </flux:text>
            @else
                <flux:text size="sm" variant="subtle">Reporte histórico</flux:text>
            @endif
        </div>

        {{-- KPIs agrupados con meta visible --}}
        @php
            $adherenceVal = is_numeric($d['adherence']) ? (float) $d['adherence'] : 0;
            $adherenceTarget = 90;
            $productivityTarget = 80;
            $hasAhtBreakdown = isset($d['avg_talk_time']) && isset($d['avg_acw_time']);
        @endphp
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="p-3 rounded-lg border border-wfm-surface-border bg-white dark:bg-wfm-navy-900 shadow-sm">
                <flux:text size="sm" variant="subtle">Llamadas atendidas</flux:text>
                <flux:heading size="xl">{{ $d['handled_calls'] ?? 0 }} / {{ $d['total_calls'] ?? 0 }}</flux:heading>
                <flux:text size="sm" :variant="$d['sla'] >= 80 ? 'success' : 'danger'">
                    SLA {{ number_format($d['sla'] ?? 0, 0) }}%
                </flux:text>
            </div>

            <div class="p-3 rounded-lg border border-wfm-surface-border bg-white dark:bg-wfm-navy-900 shadow-sm">
                <flux:text size="sm" variant="subtle">AHT</flux:text>
                <flux:heading size="xl">{{ $d['avg_handle_time'] !== null ? number_format($d['avg_handle_time'], 1) : '--' }}s</flux:heading>
                @if($hasAhtBreakdown)
                    <flux:text size="sm" variant="subtle">
                        Conv {{ number_format($d['avg_talk_time'], 1) }}s · ACW {{ number_format($d['avg_acw_time'], 1) }}s
                    </flux:text>
                @endif
            </div>

            <div class="p-3 rounded-lg border border-wfm-surface-border bg-white dark:bg-wfm-navy-900 shadow-sm">
                <flux:text size="sm" variant="subtle">Adherencia</flux:text>
                <flux:heading size="xl">{{ number_format($adherenceVal, 0) }}%</flux:heading>
                <flux:text size="sm" :variant="$adherenceVal >= $adherenceTarget ? 'success' : 'danger'">
                    Meta {{ $adherenceTarget }}%
                </flux:text>
            </div>

            <div class="p-3 rounded-lg border border-wfm-surface-border bg-white dark:bg-wfm-navy-900 shadow-sm">
                <flux:text size="sm" variant="subtle">Productividad</flux:text>
                <flux:heading size="xl">{{ number_format($d['productivity_pct'] ?? 0, 1) }}%</flux:heading>
                <flux:text size="sm" :variant="($d['productivity_pct'] ?? 0) >= $productivityTarget ? 'success' : 'warning'">
                    Meta {{ $productivityTarget }}%
                </flux:text>
            </div>
        </div>

        @if(!$isHistorical)
            {{-- Transiciones: barra de duración --}}
            @php
                $transitions = $d['transitions'] ?? [];
                $maxDuration = max(array_map(fn($t) => $t['duration'] ?? 0, $transitions) ?: [1]);
            @endphp
            <div class="p-3 rounded-lg border border-wfm-surface-border bg-white dark:bg-wfm-navy-900 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <flux:heading size="sm">Transiciones recientes</flux:heading>
                    <flux:text size="sm" variant="subtle">{{ count($transitions) }} eventos</flux:text>
                </div>
                <div class="divide-y divide-wfm-surface-border/50">
                    @forelse(array_slice($transitions, 0, 15) as $t)
                        @php
                            $barColor = match(strtoupper($t['agent_state'] ?? '')) {
                                'READY' => 'var(--color-green-500)',
                                'TALKING' => 'var(--color-blue-500)',
                                'WORK', 'ACW' => 'var(--color-purple-500)',
                                'NOT_READY' => 'var(--color-amber-500)',
                                'LOGOUT', 'OFFLINE' => 'var(--color-red-500)',
                                default => 'var(--color-zinc-400)',
                            };
                        @endphp
                        <div class="flex items-center gap-3 py-2">
                            <flux:text size="sm" variant="subtle" class="w-12 shrink-0">
                                {{ \Carbon\Carbon::parse($t['transition_time'])->format('H:i') }}
                            </flux:text>
                            <flux:text size="sm" class="flex-1">
                                {{ strtoupper($t['agent_state'] ?? '') }}
                            </flux:text>
                            <div class="h-1.5 w-24 shrink-0 rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full"
                                     style="width: {{ min(100, (int) ((($t['duration'] ?? 0) / $maxDuration) * 100)) }}%; background-color: {{ $barColor }};">
                                </div>
                            </div>
                            <flux:text size="sm" variant="subtle" class="w-16 shrink-0 text-right">
                                {{ gmdate('i:s', $t['duration'] ?? 0) }}
                            </flux:text>
                        </div>
                    @empty
                        <flux:text size="sm" variant="subtle" class="py-4 text-center block">Sin transiciones registradas</flux:text>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- Línea de Tiempo (Livewire externo) --}}
        @if(!$isHistorical)
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <div class="lg:col-span-8">
                    <x-wfm.section title="Línea de Tiempo" :description="$selectedDate->locale('es')->translatedFormat('l d F Y')">
                        <div class="h-[28rem]">
                            @livewire('operations.agent-timeline', ['employeeId' => $targetEmployee->id], key('timeline-'.$targetEmployee->id))
                        </div>
                    </x-wfm.section>
                </div>

                <div class="lg:col-span-4 space-y-4">
                    {{-- Tiempos auxiliares --}}
                    <x-wfm.section title="Tiempos Auxiliares">
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                <span class="text-wfm-navy-700">Almuerzo</span>
                                <span class="font-mono text-wfm-navy-700">{{ gmdate('H:i:s', $d['lunch'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                <span class="text-wfm-navy-700">Descansos</span>
                                <span class="font-mono text-wfm-navy-700">{{ gmdate('H:i:s', $d['break'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                <span class="text-wfm-navy-700">No Disponible</span>
                                <span class="font-mono text-wfm-navy-700">{{ gmdate('H:i:s', $d['not_ready'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between p-1.5 bg-wfm-surface/80 rounded font-medium">
                                <span class="text-wfm-navy-800">Total Auxiliar</span>
                                <span class="font-mono text-wfm-navy-800">{{ gmdate('H:i:s', ($d['lunch'] ?? 0) + ($d['break'] ?? 0) + ($d['not_ready'] ?? 0)) }}</span>
                            </div>
                        </div>
                    </x-wfm.section>

                    {{-- Ocupación / Shrinkage --}}
                    <x-wfm.section title="Indicadores">
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                <span class="text-wfm-navy-700">Ocupación</span>
                                <span class="font-mono text-wfm-navy-700">{{ $d['occupancy'] }}%</span>
                            </div>
                            @if(isset($d['shrinkage']))
                                <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                    <span class="text-wfm-navy-700">Shrinkage</span>
                                    <span class="font-mono text-wfm-navy-700">{{ $d['shrinkage'] }}%</span>
                                </div>
                            @endif
                            <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                <span class="text-wfm-navy-700">Turno</span>
                                <span class="font-mono text-wfm-navy-700">{{ $d['scheduled_entry'] }} - {{ $d['scheduled_end'] }}</span>
                            </div>
                        </div>
                    </x-wfm.section>
                </div>
            </div>
        @endif

        {{-- Adherencia por Intervalos --}}
        @if(!empty($employeeData['adherence_intervals']) && !$isHistorical)
            <x-wfm.section title="Adherencia por Intervalos (30 min)">
                <div class="flex flex-wrap gap-1.5">
                    @foreach($employeeData['adherence_intervals'] as $interval)
                        @php
                            $intervalColor = match($interval['state']) {
                                'on_track' => 'bg-wfm-success',
                                'off_track' => 'bg-wfm-danger',
                                'pending' => 'bg-wfm-surface-muted',
                                'off' => 'bg-gray-100 dark:bg-zinc-800',
                                default => 'bg-wfm-surface-muted',
                            };
                        @endphp
                        <div class="w-8 h-8 rounded {{ $intervalColor }} flex items-center justify-center group relative cursor-help"
                             title="{{ $interval['time'] }} - Esperado: {{ $interval['expected_label'] }} - Real: {{ $interval['actual'] }}">
                            <span class="text-[9px] font-mono {{ $interval['state'] === 'off' ? 'text-wfm-surface-muted' : 'text-white' }}">{{ substr($interval['time'], 0, 2) }}</span>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block z-10">
                                <div class="bg-wfm-navy-900 text-white text-[10px] rounded px-2 py-1 whitespace-nowrap shadow-lg">
                                    {{ $interval['time'] }}<br>
                                    <span class="text-wfm-success-medium">Esperado:</span> {{ $interval['expected_label'] }}<br>
                                    <span class="text-wfm-warning">Real:</span> {{ $interval['actual'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-4 mt-3 text-[10px] text-wfm-surface-muted">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-wfm-success inline-block"></span> En Cumplimiento</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-wfm-danger inline-block"></span> Fuera de Cumplimiento</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-gray-100 dark:bg-zinc-800 inline-block"></span> Fuera de Jornada</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-wfm-surface-muted inline-block"></span> Pendiente</span>
                </div>
            </x-wfm.section>
        @endif

        {{-- Cumplimiento del Horario --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <x-wfm.section title="Cumplimiento del Horario">
                    <x-wfm.table :headers="['Evento', 'Programado', 'Real', 'Diferencia', 'Estado']" compact>
                        @php
                            $entryDiff = $d['entry_diff'];
                            $entryLabel = $entryDiff !== null ? ($entryDiff <= 0 ? (string) $entryDiff : '+' . $entryDiff) . ' min' : '—';
                            $lunchReal = $d['lunch'] > 0 ? gmdate('H:i', $d['lunch']) : '—';
                            $breakReal = $d['break'] > 0 ? gmdate('H:i', $d['break']) : '—';
                        @endphp
                        @foreach([
                            ['label' => 'Entrada', 'sched' => $d['scheduled_entry'], 'real' => $d['real_entry'] ?? '--:--', 'diff' => $entryLabel, 'ok' => ($entryDiff !== null && $entryDiff <= 5)],
                            ['label' => 'Almuerzo', 'sched' => $d['lunch_start'] ?? '--:--', 'real' => $lunchReal, 'diff' => '—', 'ok' => true],
                            ['label' => 'Regreso Almuerzo', 'sched' => $d['lunch_end'] ?? '--:--', 'real' => '—', 'diff' => '—', 'ok' => true],
                            ['label' => 'Descanso', 'sched' => $d['break_start'] ?? '--:--', 'real' => $breakReal, 'diff' => '—', 'ok' => true],
                        ] as $c)
                            <flux:table.row>
                                <flux:table.cell class="font-medium">{{ $c['label'] }}</flux:table.cell>
                                <flux:table.cell><span class="font-mono">{{ $c['sched'] }}</span></flux:table.cell>
                                <flux:table.cell><span class="font-mono">{{ $c['real'] }}</span></flux:table.cell>
                                <flux:table.cell>
                                    <span class="font-mono {{ $c['ok'] ? 'text-wfm-success' : 'text-wfm-danger' }}">{{ $c['diff'] }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <x-wfm.agent-status :status="$c['ok'] ? 'available' : 'busy'" :label="$c['ok'] ? 'A tiempo' : 'Retraso'" size="xs" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </x-wfm.table>
                    @if($d['has_exceptions'])
                        <x-wfm.adherence-badge :value="0" target="1" size="xs" />
                        <span class="text-xs text-wfm-warning ml-1">Con excepción de horario</span>
                    @endif
                </x-wfm.section>
            </div>

            @if(!$isHistorical)
                <x-wfm.section title="Estado Actual">
                    <div class="space-y-3">
                        @if($d['disconnected_with_activity'] ?? false)
                            <div class="flex items-center gap-3 p-3 bg-wfm-warning/10 border border-wfm-warning/30 rounded-md">
                                <span class="live-pulse">
                                    <span class="live-pulse-dot bg-wfm-warning"></span>
                                    <span class="live-pulse-ring bg-wfm-warning"></span>
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-wfm-warning">Sesión Perdida</p>
                                    <p class="text-xs text-wfm-surface-muted">El agente tuvo actividad hoy pero actualmente aparece desconectado. Posible caída abrupta del softphone.</p>
                                </div>
                            </div>
                        @endif
                        <div class="flex items-center gap-3 p-3 bg-wfm-surface rounded-md">
                            <span class="w-3 h-3 rounded-full {{ $d['is_connected'] ? 'bg-wfm-success' : 'bg-wfm-danger' }}"></span>
                            <div>
                                <p class="text-sm font-bold text-wfm-navy-800 dark:text-white">{{ $d['current_state'] }}</p>
                                @if($d['reason'])
                                    <p class="text-xs text-wfm-surface-muted">{{ $d['reason'] }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="space-y-1 text-xs">
                            @foreach([['TALKING', $d['talk']], ['READY', $d['ready']], ['ALMUERZO', $d['lunch']], ['DESCANSO', $d['break']]] as [$label, $seconds])
                                <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                    <span class="text-wfm-navy-700">{{ $label }}</span>
                                    <span class="font-mono text-wfm-navy-700">{{ gmdate('H:i:s', $seconds) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-wfm.section>
            @else
                <x-wfm.section title="Resumen del Día">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-wfm-surface rounded-md">
                            <flux:icon.clock class="w-5 h-5 text-wfm-surface-muted" />
                            <div>
                                <p class="text-sm font-bold text-wfm-navy-800 dark:text-white">Reporte Histórico</p>
                                <p class="text-xs text-wfm-surface-muted">{{ $selectedDate->locale('es')->translatedFormat('l d F Y') }}</p>
                            </div>
                        </div>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                <span class="text-wfm-navy-700">Productividad</span>
                                <span class="font-mono text-wfm-navy-700">{{ $d['productivity_pct'] ?? '--' }}%</span>
                            </div>
                            <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                <span class="text-wfm-navy-700">Tiempo Prom. Atención</span>
                                <span class="font-mono text-wfm-navy-700">{{ $d['avg_handle_time'] !== null ? number_format($d['avg_handle_time'], 1) . 's' : '--' }}</span>
                            </div>
                            <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                <span class="text-wfm-navy-700">Llamadas Atendidas</span>
                                <span class="font-mono text-wfm-navy-700">{{ $d['handled_calls'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </x-wfm.section>
            @endif
        </div>
    @endif
</div>
