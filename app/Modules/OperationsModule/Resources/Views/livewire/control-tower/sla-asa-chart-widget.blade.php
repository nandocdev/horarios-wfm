<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5" wire:poll.120s>
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">SLA / ASA</h3>
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
