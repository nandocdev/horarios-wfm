<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm" wire:poll.120s>
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">SLA / ASA</h3>
    </div>
    @if (! $hasData)
        <div class="flex items-center justify-center h-[220px]">
            <p class="text-xs text-zinc-400 dark:text-zinc-500">Sin llamadas registradas para la fecha seleccionada</p>
        </div>
    @else
    @php
        $apexChartOptions = array_merge($chartOptions, [
            'yaxis' => [
                ['min' => 0, 'max' => 100, 'labels' => ['formatter' => ['__callback' => 'percent']], 'title' => ['text' => 'SLA', 'style' => ['fontSize' => '10px']]],
                ['opposite' => true, 'labels' => ['formatter' => ['__callback' => 'seconds']], 'title' => ['text' => 'ASA', 'style' => ['fontSize' => '10px']]],
            ],
            'tooltip' => [
                'shared' => true,
                'y' => [
                    ['formatter' => ['__callback' => 'percent']],
                    ['formatter' => ['__callback' => 'seconds']],
                    ['formatter' => ['__callback' => 'percent']],
                    ['formatter' => ['__callback' => 'seconds']],
                ],
            ],
        ]);
    @endphp
    <x-apex-chart
        id="sla-asa-chart"
        :options="$apexChartOptions"
        refresh-event="control-tower-refresh"
        height="220px"
    />
    @endif
</div>
