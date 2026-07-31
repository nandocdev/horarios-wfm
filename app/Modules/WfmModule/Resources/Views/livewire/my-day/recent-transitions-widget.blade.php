<x-wfm.section title="Transiciones Recientes">
    <div class="h-64 overflow-y-auto space-y-0.5">
        @php $transMaxDur = max(array_map(fn($t) => $t['duration'] ?? 0, $d['transitions'] ?? []) ?: [1]); @endphp
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
