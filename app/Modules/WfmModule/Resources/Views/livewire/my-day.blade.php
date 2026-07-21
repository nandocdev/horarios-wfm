<div class="space-y-4">
    @php
        $isHistorical = $employeeData['is_historical'] ?? false;
        $selDate = \Carbon\Carbon::parse($selectedDate);
        $isToday = $selDate->isToday();
        $d = $employeeData;
    @endphp

    @if(!$employeeData)
        <x-wfm.empty icon="user" message="Seleccione un empleado para ver su jornada" />
    @else
    @php $transMaxDur = max(array_map(fn($t) => $t['duration'] ?? 0, $d['transitions'] ?? []) ?: [1]); @endphp

    {{-- Header: shift selector + estado actual --}}
    <div class="card-wfm p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <flux:button wire:click="previousDay" variant="ghost" icon="chevron-left" size="xs" />
            <div>
                <div class="text-sm font-bold text-wfm-navy-800 dark:text-white">{{ $d['name'] }}</div>
                <div class="flex items-center gap-2">
                    <flux:text class="font-mono text-xs {{ $isToday ? 'text-wfm-info font-semibold' : 'text-wfm-surface-muted' }}">
                        {{ $selDate->locale('es')->translatedFormat('l d F Y') }}
                        @if($isToday)
                            <span class="text-[10px] bg-wfm-info/10 text-wfm-info px-1.5 py-0.5 rounded font-mono">Hoy</span>
                        @endif
                    </flux:text>
                    <span class="text-xs text-wfm-surface-muted">{{ $d['team'] }}</span>
                </div>
            </div>
            <flux:button wire:click="nextDay" variant="ghost" icon="chevron-right" size="xs" @if($isToday) disabled @endif />
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full {{ $d['is_connected'] ? 'bg-wfm-success animate-pulse' : 'bg-wfm-danger' }}"></span>
                <span class="text-sm font-bold">{{ $d['current_state'] }}</span>
                @if($d['reason'])
                    <span class="text-xs text-wfm-surface-muted">({{ $d['reason'] }})</span>
                @endif
            </div>
            <span class="text-xs text-wfm-surface-muted border-l border-wfm-surface-border pl-4">
                {{ gmdate('H:i:s', $d['total_seconds']) }} conectado ·
                {{ gmdate('H:i:s', $d['productive_seconds']) }} productivo
            </span>
        </div>
    </div>

    {{-- Timeline del turno con estados --}}
    @if(!empty($d['timeline_start']))
        @php
            $startH = (int) substr($d['timeline_start'], 0, 2);
            $endH = (int) substr($d['timeline_end'], 0, 2) ?: 18;
            $nowMin = $isToday ? (now()->hour * 60 + now()->minute) : 0;
            $startMin = $startH * 60;
            $endMin = $endH * 60;
            $shiftMinutes = max($endMin - $startMin, 1);
            $nowPct = $isToday ? max(0, min(100, (($nowMin - $startMin) / $shiftMinutes) * 100)) : 0;

            $stateColorMap = [
                'READY' => 'bg-wfm-success', 'TALKING' => 'bg-wfm-info',
                'WORK' => 'bg-purple-500', 'ACW' => 'bg-purple-500',
                'RESERVED' => 'bg-cyan-500',
                'NOT_READY' => 'bg-amber-500',
                'LUNCH' => 'bg-orange-400', 'NOT_READY_LUNCH' => 'bg-orange-400', 'NOT_READY_ALMUERZO' => 'bg-orange-400',
                'BREAK' => 'bg-yellow-400', 'NOT_READY_BREAK' => 'bg-yellow-400', 'NOT_READY_DESCANSO' => 'bg-yellow-400',
                'LOGOUT' => 'bg-wfm-danger', 'OFFLINE' => 'bg-wfm-danger',
            ];

            $stateLabels = [
                'TALKING' => 'TALKING', 'READY' => 'READY', 'WORK' => 'ACW', 'ACW' => 'ACW',
                'RESERVED' => 'RESERVED', 'NOT_READY' => 'NO DISP',
                'LUNCH' => 'ALMUERZO', 'NOT_READY_LUNCH' => 'ALMUERZO', 'NOT_READY_ALMUERZO' => 'ALMUERZO',
                'BREAK' => 'DESCANSO', 'NOT_READY_BREAK' => 'DESCANSO', 'NOT_READY_DESCANSO' => 'DESCANSO',
                'LOGOUT' => 'OFFLINE', 'OFFLINE' => 'OFFLINE',
            ];

            $timelineSegments = collect($d['transitions'] ?? [])
                ->sortBy('transition_time')
                ->filter(fn($t) => ($t['duration'] ?? 0) > 0)
                ->when($isToday, fn($col) => $col->filter(fn($t) => \Carbon\Carbon::parse($t['transition_time'])->lessThanOrEqualTo(now())))
                ->values();

            $totalSegSecs = $timelineSegments->sum('duration') ?: 1;
        @endphp
        <div class="card-wfm p-4">
            <div class="flex items-center text-[10px] text-wfm-surface-muted mb-3">
                <flux:icon.clock class="w-3 h-3 mr-1" />
                Turno {{ $d['timeline_start'] }} - {{ $d['timeline_end'] }}
            </div>

            <div class="relative h-14">
                {{-- Hour grid lines + labels --}}
                @for($i = 0; $i <= ($endH - $startH); $i++)
                    @php $hPos = ($endH - $startH) > 0 ? ($i / ($endH - $startH)) * 100 : 0; @endphp
                    <div class="absolute top-0 border-l border-wfm-surface-border/20" style="left: {{ $hPos }}%; height: 0.75rem;"></div>
                    <span class="absolute text-[10px] text-wfm-surface-muted font-mono -ml-2" style="left: {{ $hPos }}%;">{{ sprintf('%02d:00', $startH + $i) }}</span>
                @endfor
                <div class="absolute top-0 right-0 border-l border-wfm-surface-border/20" style="height: 0.75rem;"></div>

                {{-- Segments by state --}}
                @if($timelineSegments->isNotEmpty())
                    <div class="absolute flex gap-px" style="top: 14px; left: 0; right: 0; height: 1.25rem;">
                        @foreach($timelineSegments as $t)
                            @php
                                $st = strtoupper($t['agent_state'] ?? '');
                                $color = $stateColorMap[$st] ?? 'bg-wfm-surface-muted/30';
                                $pct = max(0.3, ($t['duration'] / $totalSegSecs) * 100);
                            @endphp
                            <div class="h-full {{ $color }} rounded-sm group relative cursor-default"
                                 style="width: {{ $pct }}%"
                                 title="{{ $stateLabels[$st] ?? $st }}: {{ gmdate('H:i:s', $t['duration']) }}">
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block z-10">
                                    <div class="bg-wfm-navy-900 text-white text-[10px] rounded px-2 py-1 whitespace-nowrap shadow-lg">
                                        {{ $stateLabels[$st] ?? $st }}<br>
                                        {{ \Carbon\Carbon::parse($t['transition_time'])->timezone('America/Panama')->format('H:i') }}
                                        · {{ gmdate('H:i:s', $t['duration']) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="absolute bg-wfm-surface-muted/20 rounded" style="top: 14px; left: 0; right: 0; height: 1.25rem;"></div>
                @endif

                {{-- Now indicator --}}
                @if($isToday)
                    <div class="absolute top-0 w-0.5 bg-wfm-danger z-10" style="left: {{ $nowPct }}%; height: 3rem;">
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 text-[9px] text-wfm-danger font-bold whitespace-nowrap">Ahora</span>
                    </div>
                @endif
            </div>

            {{-- Legend --}}
            @if($timelineSegments->isNotEmpty())
                @php
                    $seen = [];
                    $legend = [];
                    foreach ($timelineSegments as $t) {
                        $st = strtoupper($t['agent_state'] ?? '');
                        $label = $stateLabels[$st] ?? $st;
                        if (!in_array($label, $seen) && isset($stateColorMap[$st])) {
                            $seen[] = $label;
                            $legend[] = ['label' => $label, 'color' => $stateColorMap[$st]];
                        }
                    }
                @endphp
                @if(count($legend) > 0)
                    <div class="flex flex-wrap gap-x-3 gap-y-0.5 mt-3">
                        @foreach($legend as $li)
                            <span class="inline-flex items-center gap-1 text-[10px] text-wfm-surface-muted">
                                <span class="w-2 h-2 rounded-sm {{ $li['color'] }}"></span>
                                {{ $li['label'] }}
                            </span>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    @endif

    {{-- Tráfico y Calidad --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-wfm.kpi :value="$d['total_calls'] ?? 0" :label="'Llamadas · SLA ' . ($d['sla'] ?? 0) . '%'" icon="phone" color="info" />
        <x-wfm.kpi :value="($d['avg_handle_time'] ? number_format($d['avg_handle_time'], 1) . 's' : '--')" label="AHT" :comparison="($d['avg_talk_time'] ? 'T ' . number_format($d['avg_talk_time'], 1) . 's' : '') . ($d['avg_talk_time'] && $d['avg_acw_time'] ? ' · ' : '') . ($d['avg_acw_time'] ? 'ACW ' . number_format($d['avg_acw_time'], 1) . 's' : '')" icon="clock" color="success" />
        @php $adhVal = is_numeric($d['adherence'] ?? null) ? (float) $d['adherence'] : 0; @endphp
        <x-wfm.kpi :value="$adhVal . '%'" label="Adherencia" :trend="$adhVal . '%'" trend-direction="{{ $adhVal >= 80 ? 'up' : 'down' }}" icon="check-badge" :color="$adhVal >= 80 ? 'success' : ($adhVal >= 60 ? 'warning' : 'danger')" />
        <x-wfm.kpi :value="($d['occupancy'] ?? 0) . '%'" label="Ocupación" icon="cpu-chip" />
    </div>

    {{-- Cuerpo principal: transiciones + cumplimiento --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-wfm.section title="Transiciones Recientes">
            <div class="h-64 overflow-y-auto space-y-0.5">
                @forelse(($d['transitions'] ?? []) as $t)
                    @php
                        $st = strtoupper($t['agent_state'] ?? '');
                        $stColor = match($st) {
                            'READY' => 'bg-wfm-success',
                            'TALKING' => 'bg-wfm-info',
                            'WORK', 'ACW' => 'bg-purple-500',
                            'NOT_READY' => 'bg-wfm-warning',
                            'LOGOUT', 'OFFLINE' => 'bg-wfm-danger',
                            default => 'bg-wfm-surface-muted',
                        };
                        $dur = $t['duration'] ?? 0;
                    @endphp
                    <div class="flex items-center gap-2 py-1 px-2 rounded hover:bg-wfm-surface/50 text-xs">
                        <span class="w-2 h-2 rounded-full {{ $stColor }} flex-shrink-0"></span>
                        <span class="font-mono text-wfm-surface-muted w-10">{{ \Carbon\Carbon::parse($t['transition_time'])->timezone('America/Panama')->format('H:i') }}</span>
                        <span class="font-medium flex-1">{{ $st }}</span>
                        <div class="flex items-center gap-1">
                            <div class="h-1.5 bg-wfm-surface rounded-full w-16 overflow-hidden">
                                <div class="h-full rounded-full {{ $stColor }}" style="width: {{ min(100, ($dur / $transMaxDur) * 100) }}%"></div>
                            </div>
                            <span class="font-mono text-wfm-surface-muted w-12 text-right">{{ $dur >= 3600 ? gmdate('H:i:s', $dur) : gmdate('i:s', $dur) }}</span>
                        </div>
                    </div>
                @empty
                    <x-wfm.empty icon="clock" message="Sin transiciones" class="h-48" />
                @endforelse
            </div>
        </x-wfm.section>

        <x-wfm.section title="Cumplimiento del Horario">
            @php
                $entryDiff = $d['entry_diff'] ?? null;
                $entryLabel = $entryDiff !== null ? ($entryDiff <= 0 ? (string) $entryDiff : '+' . $entryDiff) . ' min' : '—';
                $compliance = [
                    ['label' => 'Entrada', 'sched' => $d['scheduled_entry'] ?? '--:--', 'real' => $d['real_entry'] ?? '--:--', 'diff' => $entryLabel, 'ok' => ($entryDiff !== null && $entryDiff <= 5)],
                    ['label' => 'Almuerzo', 'sched' => $d['lunch_start'] ?? '--:--', 'real' => $d['lunch'] > 0 ? gmdate('H:i', $d['lunch']) : '—', 'diff' => '—', 'ok' => true],
                    ['label' => 'Descanso', 'sched' => $d['break_start'] ?? '--:--', 'real' => $d['break'] > 0 ? gmdate('H:i', $d['break']) : '—', 'diff' => '—', 'ok' => true],
                ];
            @endphp
            <x-wfm.table :headers="['', 'Programado', 'Real', 'Estado']" compact>
                @foreach($compliance as $c)
                    <flux:table.row>
                        <flux:table.cell class="font-medium text-xs">{{ $c['label'] }}</flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $c['sched'] }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $c['real'] }}</span></flux:table.cell>
                        <flux:table.cell>
                            <x-wfm.agent-status :status="$c['ok'] ? 'available' : 'busy'" :label="$c['diff']" size="xs" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </x-wfm.table>
            @if($d['has_exceptions'] ?? false)
                <div class="mt-2 flex items-center gap-1.5 text-xs text-wfm-warning">
    <flux:icon.exclamation-triangle class="w-3.5 h-3.5" />
    <span>Con excepción de horario</span>
</div>
            @endif
        </x-wfm.section>
    </div>

    {{-- Desglose Not Ready por motivo --}}
    @if(!empty($d['not_ready_by_reason']) && count($d['not_ready_by_reason']) > 0)
        @php
            $nrTotal = array_sum($d['not_ready_by_reason']);
            $nrColors = ['bg-amber-400', 'bg-blue-400', 'bg-green-400', 'bg-rose-400', 'bg-violet-400', 'bg-gray-400'];
            $nrIdx = 0;
        @endphp
        <x-wfm.section title="Desglose Not Ready por Motivo">
            <div class="flex flex-col gap-2">
                <div class="flex h-5 rounded-full overflow-hidden">
                    @foreach($d['not_ready_by_reason'] as $reason => $secs)
                        @php $pct = $nrTotal > 0 ? ($secs / $nrTotal) * 100 : 0; @endphp
                        <div class="{{ $nrColors[$nrIdx % count($nrColors)] }}" style="width: {{ $pct }}%" title="{{ $reason }}: {{ gmdate('H:i', $secs) }}"></div>
                        @php $nrIdx++; @endphp
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-[10px]">
                    @php $nrIdx = 0; @endphp
                    @foreach($d['not_ready_by_reason'] as $reason => $secs)
                        @php $pct = $nrTotal > 0 ? round(($secs / $nrTotal) * 100, 1) : 0; @endphp
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded inline-block {{ $nrColors[$nrIdx % count($nrColors)] }}"></span>
                            {{ $reason === 'SIN_MOTIVO' ? 'Sin Motivo' : $reason }}
                            <span class="font-mono text-wfm-surface-muted">{{ gmdate('H:i', $secs) }} ({{ $pct }}%)</span>
                        </span>
                        @php $nrIdx++; @endphp
                    @endforeach
                </div>
            </div>
        </x-wfm.section>
    @endif

    {{-- Llamadas por Cola vs Distribución AHT --}}
    @if(!empty($d['calls_by_queue']))
        @php $queueData = is_array($d['calls_by_queue']) ? $d['calls_by_queue'] : (method_exists($d['calls_by_queue'], 'toArray') ? $d['calls_by_queue']->toArray() : []); @endphp
        @if(count($queueData) > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <x-wfm.section title="Llamadas por Cola">
                    <x-wfm.table :headers="['Cola', 'Llamadas', 'AHT', 'Talk Time']" compact>
                        @php $qTotalCalls = 0; $qTotalAht = 0; $qCount = 0; @endphp
                        @foreach($queueData as $q)
                            @php
                                $qc = (int) ($q['handled'] ?? $q['total_offered'] ?? 0);
                                $qaht = (float) ($q['avg_aht'] ?? 0);
                                $qTalk = (float) ($q['avg_talk'] ?? 0);
                                $qTotalCalls += $qc;
                                $qTotalAht += $qaht;
                                $qCount++;
                            @endphp
                            <flux:table.row>
                                <flux:table.cell class="font-medium text-xs">{{ $q['queue_name'] ?? '—' }}</flux:table.cell>
                                <flux:table.cell><span class="font-mono text-xs">{{ $qc }}</span></flux:table.cell>
                                <flux:table.cell><span class="font-mono text-xs">{{ $qaht > 0 ? number_format($qaht, 1) . 's' : '—' }}</span></flux:table.cell>
                                <flux:table.cell><span class="font-mono text-xs">{{ $qTalk > 0 ? number_format($qTalk, 1) . 's' : '—' }}</span></flux:table.cell>
                            </flux:table.row>
                        @endforeach
                        @if($qCount > 1)
                            <flux:table.row class="font-bold">
                                <flux:table.cell class="text-xs">Total / Prom</flux:table.cell>
                                <flux:table.cell><span class="font-mono text-xs">{{ $qTotalCalls }}</span></flux:table.cell>
                                <flux:table.cell><span class="font-mono text-xs">{{ $qCount > 0 ? number_format($qTotalAht / $qCount, 1) : 0 }}s</span></flux:table.cell>
                                <flux:table.cell><span class="font-mono text-xs">—</span></flux:table.cell>
                            </flux:table.row>
                        @endif
                    </x-wfm.table>
                </x-wfm.section>

                <x-wfm.section title="Distribución AHT por Cola">
                    <div class="relative h-48">
                        @php
                            $maxCalls = max(array_map(fn($q) => (int) ($q['handled'] ?? $q['total_offered'] ?? 0), $queueData)) ?: 1;
                            $maxAht = max(array_map(fn($q) => (float) ($q['avg_aht'] ?? 0), $queueData)) ?: 1;
                        @endphp
                        <svg viewBox="0 0 100 50" class="w-full h-full">
                            @foreach($queueData as $q)
                                @php
                                    $qc = (int) ($q['handled'] ?? $q['total_offered'] ?? 0);
                                    $qaht = (float) ($q['avg_aht'] ?? 0);
                                    $cx = 10 + (($qc / $maxCalls) * 80);
                                    $cy = 45 - (($qaht / max($maxAht, 1)) * 40);
                                    $r = max(2, min(8, $qc / $maxCalls * 8));
                                @endphp
                                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="rgba(59,130,246,0.3)" stroke="rgb(59,130,246)" stroke-width="0.5" />
                                <text x="{{ $cx }}" y="{{ $cy - $r - 1 }}" font-size="2" text-anchor="middle" fill="#6b7280">{{ $q['queue_name'] ?? '' }}</text>
                            @endforeach
                            <text x="2" y="10" font-size="2.5" fill="#9ca3af">AHT</text>
                            <text x="98" y="48" font-size="2.5" text-anchor="end" fill="#9ca3af">Volumen</text>
                        </svg>
                    </div>
                </x-wfm.section>
            </div>
        @endif
    @endif

    {{-- Estado Actual (solo hoy) --}}
    @if(!$isHistorical)
        <x-wfm.section title="Estado Actual">
            <div class="space-y-2 text-xs">
                @foreach([['TALKING', $d['talk'] ?? 0], ['READY', $d['ready'] ?? 0],
                          ['ACW/WORK', $d['acw'] ?? 0], ['RESERVED', $d['reserved'] ?? 0],
                          ['ALMUERZO', $d['lunch'] ?? 0], ['DESCANSO', $d['break'] ?? 0],
                          ['NOT READY', $d['not_ready'] ?? 0], ['OFFLINE', $d['offline'] ?? 0]] as [$label, $seconds])
                    @php
                        $pct = ($d['total_seconds'] ?? 1) > 0 ? round(($seconds / max($d['total_seconds'], 1)) * 100, 1) : 0;
                    @endphp
                    <div class="flex items-center gap-2 p-1.5 bg-wfm-surface rounded">
                        <span class="w-16 text-wfm-navy-700 font-medium">{{ $label }}</span>
                        <div class="flex-1 h-2 bg-wfm-surface-border rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ match($label) {
                                'TALKING' => 'bg-wfm-info',
                                'READY' => 'bg-wfm-success',
                                'ACW/WORK' => 'bg-purple-500',
                                'RESERVED' => 'bg-cyan-500',
                                'ALMUERZO', 'DESCANSO' => 'bg-wfm-warning',
                                'NOT READY' => 'bg-amber-500',
                                'OFFLINE' => 'bg-wfm-danger',
                                default => 'bg-wfm-surface-muted',
                            } }}" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="font-mono text-wfm-navy-700 w-16 text-right">{{ gmdate('H:i:s', $seconds) }}</span>
                        <span class="text-wfm-surface-muted w-10 text-right">{{ $pct }}%</span>
                    </div>
                @endforeach
            </div>
        </x-wfm.section>
    @else
        <x-wfm.section title="Resumen del Día">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <x-wfm.kpi :value="($d['productivity_pct'] ?? 0) . '%'" label="Productividad" icon="chart-pie" color="info" />
                <x-wfm.kpi :value="$d['handled_calls'] ?? 0" label="Llamadas Atendidas" icon="phone" color="success" />
                <x-wfm.kpi :value="($d['avg_handle_time'] ?? 0) . 's'" label="AHT Promedio" icon="clock" />
                <x-wfm.kpi :value="gmdate('H:i:s', $d['aux_seconds'] ?? 0)" label="Tiempo Auxiliar" icon="clock" color="warning" />
            </div>
        </x-wfm.section>
    @endif
    @endif
</div>
