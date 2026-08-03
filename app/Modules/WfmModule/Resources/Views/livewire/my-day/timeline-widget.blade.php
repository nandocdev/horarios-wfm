<div class="contents">
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

        $sincePct = 0;
        $windowWidth = 100;
        $timelineSegments = collect($d['transitions'] ?? [])
            ->sortBy('transition_time')
            ->filter(fn($t) => ($t['duration'] ?? 0) > 0)
            ->when($isToday, function ($col) use (&$sincePct, &$windowWidth, $startMin, $shiftMinutes) {
                $since = now()->subMinutes(60);
                $sincePct = max(0, min(100, (($since->hour * 60 + $since->minute - $startMin) / $shiftMinutes) * 100));
                $windowWidth = max(1, (60 / $shiftMinutes) * 100);

                return $col->filter(fn($t) => \Carbon\Carbon::parse($t['transition_time'])->greaterThanOrEqualTo($since));
            })
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

            {{-- Base barra completa gris --}}
            <div class="absolute bg-wfm-surface-muted/20 rounded-sm" style="top: 14px; height: 1.25rem; left: 0; right: 0;"></div>

            {{-- Segments by state (ultimos 60 min) --}}
            @if($timelineSegments->isNotEmpty())
                <div class="absolute flex gap-px" style="top: 14px; height: 1.25rem; left: {{ $sincePct }}%; width: {{ $windowWidth }}%;">
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
</div>
