<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="chart-bar" variant="mini" class="text-blue-600" />
                Tendencias
            </flux:heading>
            <flux:subheading>Evolución de KPIs en el tiempo</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="metric" size="sm" class="md:w-44">
                @foreach($metricOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="period" size="sm" class="md:w-32">
                @foreach($periodOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            @if($period === 'weekly')
                <flux:input type="number" wire:model.live="weeks" min="4" max="52" size="sm" class="md:w-20" label="Semanas" />
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach(['occupancy', 'adherence', 'service_level', 'shrinkage_pct'] as $m)
            @php
                $trendLabels = [
                    'occupancy' => ['suffix' => '%', 'higher' => 'better', 'good' => 85, 'bad' => 70],
                    'adherence' => ['suffix' => '%', 'higher' => 'better', 'good' => 90, 'bad' => 80],
                    'service_level' => ['suffix' => '%', 'higher' => 'better', 'good' => 80, 'bad' => 60],
                    'shrinkage_pct' => ['suffix' => '%', 'higher' => 'worse', 'good' => 20, 'bad' => 30],
                ];
                $latest = \App\Modules\AnalyticsModule\Models\DailyKpi::where('granularity', 'global')
                    ->orderByDesc('evaluation_date')->first();
                $val = $latest?->$m;
                $meta = $trendLabels[$m] ?? ['suffix' => '', 'higher' => 'neutral', 'good' => null, 'bad' => null];
                $color = 'text-slate-400';
                if ($val !== null && $meta['good'] !== null) {
                    $color = $meta['higher'] === 'better'
                        ? ($val >= $meta['good'] ? 'text-green-600' : ($val >= $meta['bad'] ? 'text-amber-600' : 'text-red-600'))
                        : ($val <= $meta['good'] ? 'text-green-600' : ($val <= $meta['bad'] ? 'text-amber-600' : 'text-red-600'));
                }
            @endphp
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $metricOptions[$m] ?? $m }}</p>
                <p class="text-2xl font-bold {{ $color }} mt-1">
                    {{ $val !== null ? number_format((float) $val, 1) . ($meta['suffix'] ?? '') : '—' }}
                </p>
                <p class="text-[10px] text-slate-400 mt-0.5">Último día disponible</p>
            </flux:card>
        @endforeach
    </div>

    <flux:card class="p-4">
        <div class="flex items-center justify-between mb-4">
            <flux:heading size="sm">
                {{ $metricOptions[$metric] ?? $metric }} — Tendencia {{ $periodOptions[$period] ?? $period }}
            </flux:heading>
            @if($metricMeta['higher'] === 'better')
                <div class="flex gap-3 text-[10px]">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-green-500"></span> Bueno</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-amber-500"></span> Regular</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-red-500"></span> Crítico</span>
                </div>
            @endif
        </div>

        <div class="space-y-1.5 max-h-[600px] overflow-y-auto">
            @forelse($trends as $t)
                @php
                    $val = $t['value'];
                    $barColor = 'bg-blue-500';
                    $textColor = 'text-slate-700';

                    if ($val !== null && $metricMeta['good'] !== null) {
                        $isGood = $metricMeta['higher'] === 'better' ? $val >= $metricMeta['good'] : $val <= $metricMeta['good'];
                        $isBad = $metricMeta['higher'] === 'better' ? $val < $metricMeta['bad'] : $val > $metricMeta['bad'];

                        $barColor = $isGood ? 'bg-green-500' : ($isBad ? 'bg-red-500' : 'bg-amber-500');
                        $textColor = $isGood ? 'text-green-700' : ($isBad ? 'text-red-700' : 'text-amber-700');
                    }

                    $label = $t['label'];
                    $displayVal = $val !== null
                        ? ($metric === 'aht_seconds' || $metric === 'asa_seconds'
                            ? sprintf('%02d:%02d', floor($val / 60), (int) $val % 60)
                            : number_format($val, 1) . $metricMeta['suffix'])
                        : '—';

                    $barWidth = $val !== null ? min(100, abs($val) / ($metricMeta['good'] ?: 100) * 100) : 0;
                    $barWidth = max(2, $barWidth);
                @endphp
                <div class="flex items-center gap-3">
                    <span class="w-24 text-xs font-mono font-semibold text-slate-500 truncate" title="{{ $label }}">{{ $label }}</span>
                    <div class="flex-1 h-5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $barColor }} transition-all duration-300" style="width: {{ $barWidth > 100 ? 100 : $barWidth }}%"></div>
                    </div>
                    <span class="w-20 text-right text-xs font-mono font-bold {{ $textColor }}">{{ $displayVal }}</span>
                </div>
            @empty
                <div class="p-6 text-center text-slate-400 italic">
                    Sin datos de KPIs globales para el período seleccionado.
                </div>
            @endforelse
        </div>
    </flux:card>
</div>
