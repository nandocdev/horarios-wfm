<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm" wire:poll.120s>
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Occupancy por Hora</h3>
    </div>
    <div wire:ignore>
        <div x-data="{
            chart: null,
            options: @json($chartOptions),
            init() {
                if (typeof ApexCharts !== 'undefined') {
                    this.chart = new ApexCharts(this.$el, this.options);
                    this.chart.render();
                    this.$wire.$on('control-tower-refresh', () => {
                        if (this.chart) this.chart.updateOptions(this.options);
                    });
                }
            }
        }"></div>
    </div>
</div>
