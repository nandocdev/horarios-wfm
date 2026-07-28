<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5" wire:poll.300s>
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Forecast vs Real</h3>
    @if ($hasForecast)
        <div wire:ignore>
            <div x-data="{
                chart: null,
                options: @json($chartOptions),
                init() {
                    if (typeof ApexCharts !== 'undefined') {
                        this.chart = new ApexCharts(this.$el, this.options);
                        this.chart.render();
                    }
                }
            }"></div>
        </div>
    @else
        <div class="flex items-center justify-center h-48 text-sm text-zinc-400 dark:text-zinc-500">
            <div class="text-center">
                <flux:icon name="chart-bar" class="w-8 h-8 mx-auto mb-2 text-zinc-300 dark:text-zinc-600" />
                <p>No hay forecast publicado para hoy</p>
                <p class="text-xs mt-1">Genera un forecast desde Planificación</p>
            </div>
        </div>
    @endif
</div>
