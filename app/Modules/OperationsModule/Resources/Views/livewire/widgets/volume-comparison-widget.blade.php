<flux:card>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="lg">Comparativo de Volumen: Actual vs. Anterior</flux:heading>
            <flux:subheading>Distribución diaria de llamadas (Atendidas/Abandonadas) comparando semanas.
            </flux:subheading>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-1.5">
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                <span class="text-xs text-zinc-500">Atendidas</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <span class="text-xs text-zinc-500">Abandonadas</span>
            </div>
            <flux:separator vertical />
            <div class="flex items-center gap-2">
                <div class="flex flex-col gap-0.5">
                    <span class="text-[10px] font-bold text-zinc-400 uppercase">Semanas</span>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-medium">S1: {{ $volumeComparison['curr_week_label'] }}</span>
                        <span class="text-xs font-medium text-zinc-400">S2: {{ $volumeComparison['prev_week_label'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-data="{
            chart: null,
            init() {
                this.chart = new ApexCharts(this.$refs.volumeChart, {
                    chart: {
                        type: 'bar',
                        height: 350,
                        stacked: true,
                        toolbar: { show: true }
                    },
                    series: [
                        // Grupo Semana Anterior
                        { 
                            name: 'Atendidas (' + @js($volumeComparison['prev_week_label']) + ')', 
                            group: 'anterior', 
                            data: @js($volumeComparison['previous_handled']) 
                        },
                        { 
                            name: 'Abandonadas (' + @js($volumeComparison['prev_week_label']) + ')', 
                            group: 'anterior', 
                            data: @js($volumeComparison['previous_abandoned']) 
                        },
                        // Grupo Semana Actual
                        { 
                            name: 'Atendidas (' + @js($volumeComparison['curr_week_label']) + ')', 
                            group: 'actual', 
                            data: @js($volumeComparison['current_handled']) 
                        },
                        { 
                            name: 'Abandonadas (' + @js($volumeComparison['curr_week_label']) + ')', 
                            group: 'actual', 
                            data: @js($volumeComparison['current_abandoned']) 
                        }
                    ],
                    xaxis: {
                        categories: @js($volumeComparison['labels']),
                        axisBorder: { show: true },
                        labels: { style: { colors: '#71717a', fontSize: '12px' } }
                    },
                    colors: ['#22c55e', '#ef4444', '#86efac', '#fca5a5'],
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '60%',
                            borderRadius: 4,
                        }
                    },
                    dataLabels: { enabled: false },
                    legend: { position: 'top' },
                    tooltip: { 
                        shared: true, 
                        intersect: false,
                        theme: 'light'
                    }
                });
                this.chart.render();
            }
        }" x-init="init()" wire:ignore>
        <div x-ref="volumeChart" class="w-full min-h-[320px]"></div>
    </div>
</flux:card>
