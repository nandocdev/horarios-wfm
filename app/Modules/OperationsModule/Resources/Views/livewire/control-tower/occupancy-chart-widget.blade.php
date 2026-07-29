<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm" wire:poll.120s>
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Occupancy por Hora</h3>
    </div>
    @if($hasData)
        <div wire:ignore
             x-data='{
                 chart: null,
                 opts: @json($chartOptions),
                 init() {
                     if (typeof ApexCharts === "undefined") return;
                     this.opts.yaxis = { min: 0, max: 100, labels: { formatter: v => v + "%" } };
                     this.opts.tooltip = { y: { formatter: v => v + "%" } };
                     this.chart = new ApexCharts(this.$refs.chart, this.opts);
                     this.chart.render();
                     this.$wire.$on("control-tower-refresh", () => {
                         if (this.chart) this.chart.updateOptions(this.opts);
                     });
                 }
             }'>
            <div x-ref="chart"></div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-10 text-zinc-400 dark:text-zinc-500">
            <flux:icon.chart-bar class="w-10 h-10 mb-2" />
            <p class="text-sm font-medium">Sin datos de ocupación</p>
            <p class="text-xs mt-1">Las métricas por intervalo se generan cada 15 minutos.</p>
        </div>
    @endif
</div>
