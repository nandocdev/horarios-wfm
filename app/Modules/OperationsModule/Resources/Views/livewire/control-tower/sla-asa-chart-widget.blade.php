<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm" wire:poll.120s>
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">SLA / ASA</h3>
    </div>
    <div wire:ignore x-data="{
        chart: null,
        opts: @json($chartOptions),
        init() {
            if (typeof ApexCharts === 'undefined') return;
            this.opts.yaxis = [
                { min: 0, max: 100, labels: { formatter: v => v + '%' }, title: { text: 'SLA', style: { fontSize: '10px' } } },
                { opposite: true, labels: { formatter: v => v + 's' }, title: { text: 'ASA', style: { fontSize: '10px' } } },
            ];
            this.opts.tooltip = {
                shared: true,
                y: [
                    { formatter: v => v + '%' },
                    { formatter: v => v + 's' },
                    { formatter: v => v + '%' },
                    { formatter: v => v + 's' },
                ],
            };
            this.chart = new ApexCharts(this.$refs.chart, this.opts);
            this.chart.render();
            this.$wire.$on('control-tower-refresh', () => {
                if (this.chart) this.chart.updateOptions(this.opts);
            });
        }
    }">
        <div x-ref="chart"></div>
    </div>
</div>
