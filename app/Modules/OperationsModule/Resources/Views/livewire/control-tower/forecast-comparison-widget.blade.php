<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm" wire:poll.300s>
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Forecast vs Real (15 min)</h3>
    </div>
    @if ($hasForecast)
        @php
            $apexChartOptions = array_merge($chartOptions, [
                'yaxis' => ['labels' => ['formatter' => ['__callback' => 'integer']]],
                'tooltip' => ['shared' => true, 'y' => ['formatter' => ['__callback' => 'calls']]],
            ]);
        @endphp
        <x-apex-chart
            id="forecast-comparison-chart"
            :options="$apexChartOptions"
            height="220px"
        />
    @else
        <div class="flex flex-col items-center justify-center h-48 text-sm text-zinc-400 dark:text-zinc-500">
            <flux:icon name="chart-bar-square" class="w-8 h-8 mb-3 text-zinc-300 dark:text-zinc-600" />
            <p class="font-medium text-zinc-500">No hay forecast publicado para hoy</p>
        </div>
    @endif
</div>
