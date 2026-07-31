@php
    $queueData = collect($d['calls_by_queue'] ?? []);
    $scatterData = $d['call_scatter_data'] ?? [];
    $hasAnyData = $queueData->isNotEmpty() || ! empty($scatterData);
@endphp
@if($hasAnyData)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-wfm.section title="Llamadas por Cola">
            <x-wfm.table :headers="['Cola', 'Llamadas', 'AHT', 'T. Acumulado Hablado', 'Máx', 'Mín', 'Media', 'Desv. Est.']" compact>
                @php $qTotalCalls = 0; $qTotalTalk = 0; $qCount = 0; @endphp
                @foreach($queueData as $q)
                    @php
                        $qc = (int) ($q->handled ?? $q->total_offered ?? 0);
                        $qaht = $q->avg_aht ? (float) $q->avg_aht : 0;
                        $qTalk = (int) ($q->total_talk ?? 0);
                        $qMax = (int) ($q->max_talk ?? 0);
                        $qMin = (int) ($q->min_talk ?? 0);
                        $qMean = (float) ($q->mean_talk ?? 0);
                        $qStd = (float) ($q->std_talk ?? 0);
                        $qTotalCalls += $qc;
                        $qTotalTalk += $qTalk;
                        $qCount++;
                    @endphp
                    <flux:table.row>
                        <flux:table.cell class="font-medium text-xs">{{ $q->queue_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $qc }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $qaht > 0 ? number_format($qaht, 1) . 's' : '—' }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $qTalk > 0 ? gmdate('H:i:s', $qTalk) : '—' }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $qMax > 0 ? $qMax . 's' : '—' }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $qMin > 0 ? $qMin . 's' : '—' }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs text-wfm-info">{{ $qMean > 0 ? number_format($qMean, 1) . 's' : '—' }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs text-wfm-warning">{{ $qStd > 0 ? '±' . number_format($qStd, 1) . 's' : '—' }}</span></flux:table.cell>
                    </flux:table.row>
                @endforeach
                @if($qCount > 1)
                    @php 
                        $sStats = $d['call_scatter_stats'] ?? ['max' => 0, 'min' => 0, 'mean' => 0, 'std' => 0]; 
                    @endphp
                    <flux:table.row class="font-bold">
                        <flux:table.cell class="text-xs">Total Global</flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $qTotalCalls }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $qTotalCalls > 0 ? number_format($qTotalTalk / $qTotalCalls, 1) . 's' : '—' }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $qTotalTalk > 0 ? gmdate('H:i:s', $qTotalTalk) : '—' }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $sStats['max'] > 0 ? $sStats['max'] . 's' : '—' }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $sStats['min'] > 0 ? $sStats['min'] . 's' : '—' }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs text-wfm-info">{{ $sStats['mean'] > 0 ? number_format($sStats['mean'], 1) . 's' : '—' }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs text-wfm-warning">{{ $sStats['std'] > 0 ? '±' . number_format($sStats['std'], 1) . 's' : '—' }}</span></flux:table.cell>
                    </flux:table.row>
                @endif
            </x-wfm.table>
        </x-wfm.section>

        <x-wfm.section title="Distribución AHT por Cola">
            <x-slot:actions>
                @php $sStats = $d['call_scatter_stats'] ?? ['max' => 0, 'min' => 0, 'mean' => 0, 'std' => 0]; @endphp
                <div class="flex items-center gap-3 text-[10px] text-wfm-surface-muted bg-wfm-surface px-2 py-1 rounded border border-wfm-surface-border/50">
                    <div class="flex flex-col items-center"><span class="font-bold text-wfm-navy-800 dark:text-white leading-none">{{ number_format($sStats['max']) }}s</span><span class="text-[9px] mt-0.5">Máx</span></div>
                    <div class="w-px h-5 bg-wfm-surface-border"></div>
                    <div class="flex flex-col items-center"><span class="font-bold text-wfm-navy-800 dark:text-white leading-none">{{ number_format($sStats['min']) }}s</span><span class="text-[9px] mt-0.5">Mín</span></div>
                    <div class="w-px h-5 bg-wfm-surface-border"></div>
                    <div class="flex flex-col items-center"><span class="font-bold text-wfm-info leading-none">{{ number_format($sStats['mean'], 1) }}s</span><span class="text-[9px] mt-0.5">Media</span></div>
                    <div class="w-px h-5 bg-wfm-surface-border"></div>
                    <div class="flex flex-col items-center"><span class="font-bold text-wfm-warning leading-none">±{{ number_format($sStats['std'], 1) }}s</span><span class="text-[9px] mt-0.5">Desv. Est.</span></div>
                </div>
            </x-slot:actions>
            @php
                $scatterColors = ['#3b82f6','#ef4444','#10b981','#f59e0b','#8b5cf6','#ec4899','#06b6d4','#f97316','#6366f1','#14b8a6','#e11d48','#84cc16','#d946ef','#0ea5e9','#fb923c','#22d3ee','#a855f7','#34d399','#f472b6'];
                $series = $d['call_scatter_data'] ?? [];
                $seriesWithColors = array_map(fn ($s, $i) => array_merge($s, ['color' => $scatterColors[$i % count($scatterColors)]]), $series, array_keys($series));

                $schedEntry = $d['scheduled_entry'] ?? '06:00';
                $shiftStartParts = explode(':', $schedEntry);
                $shiftStartTotal = ((int) ($shiftStartParts[0] ?? 6)) * 60 + ((int) ($shiftStartParts[1] ?? 0));

                $lunchMin = ($d['lunch_start'] ?? null) && $schedEntry !== '--:--'
                    ? (int) \Carbon\Carbon::parse($d['lunch_start'])->diffInMinutes(\Carbon\Carbon::parse($schedEntry), false)
                    : null;

                $chartOptions = json_encode([
                    'chart' => [
                        'type' => 'scatter',
                        'toolbar' => ['show' => false],
                        'zoom' => ['enabled' => false],
                        'animations' => ['enabled' => false],
                        'offsetX' => 0,
                        'offsetY' => 0,
                        'parentHeightOffset' => 0,
                    ],
                    'colors' => $scatterColors,
                    'series' => $seriesWithColors,
                    'xaxis' => [
                        'title' => ['text' => 'Hora del día', 'style' => ['fontSize' => '11px']],
                        'min' => $d['call_scatter_x_min'] ?? -60,
                        'max' => $d['call_scatter_x_max'] ?? 540,
                        'tickAmount' => 10,
                        'labels' => ['formatter' => 'function(v) { let t=' . $shiftStartTotal . '+v; if(t<0)t+=1440; let h=Math.floor(t/60)%24; let m=Math.floor(t%60); return String(h).padStart(2,"0")+":"+String(m).padStart(2,"0"); }'],
                    ],
                    'yaxis' => [
                        'title' => ['text' => 'Talk Time (segundos)', 'style' => ['fontSize' => '11px']],
                        'labels' => ['formatter' => 'function(v) { return v.toFixed(0) + "s"; }'],
                    ],
                    'markers' => [
                        'size' => 6,
                        'strokeWidth' => 1,
                        'strokeOpacity' => 0.6,
                    ],
                    'tooltip' => [
                        'custom' => 'function({seriesIndex, dataPointIndex, w}) {
                            let d = w.config.series[seriesIndex].data[dataPointIndex];
                            let t = ' . $shiftStartTotal . ' + d.x;
                            if (t < 0) t += 1440;
                            let h = Math.floor(t / 60) % 24;
                            let m = Math.floor(t % 60);
                            let timeStr = String(h).padStart(2,"0") + ":" + String(m).padStart(2,"0");
                            return "<div class=\"px-3 py-2 text-xs\">" +
                                "<strong>" + w.config.series[seriesIndex].name + "</strong><br>" +
                                "Hora: " + timeStr + "<br>" +
                                "Talk Time: " + d.y + "s" +
                                "</div>";
                        }',
                    ],
                    'grid' => [
                        'show' => true,
                        'borderColor' => '#e5e7eb',
                        'strokeDashArray' => 2,
                        'padding' => ['left' => 20, 'right' => 10, 'top' => 10, 'bottom' => 10],
                    ],
                    'annotations' => $lunchMin !== null ? [
                        'xaxis' => [[
                            'x' => $lunchMin,
                            'borderColor' => '#f59e0b',
                            'strokeDashArray' => 4,
                            'label' => [
                                'borderColor' => '#f59e0b',
                                'position' => 'top',
                                'style' => ['color' => '#fff', 'background' => '#f59e0b', 'fontSize' => '9px'],
                                'text' => 'Almuerzo',
                            ],
                        ]],
                    ] : new \stdClass(),
                ]);
            @endphp
            <x-apex-chart id="aht-scatter" :options="$chartOptions" height="280" />
        </x-wfm.section>
    </div>
@endif
