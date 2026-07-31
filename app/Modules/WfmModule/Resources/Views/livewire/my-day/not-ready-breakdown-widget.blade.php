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
