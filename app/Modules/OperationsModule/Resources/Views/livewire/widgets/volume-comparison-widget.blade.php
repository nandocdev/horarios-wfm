<flux:card>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="lg">Comparativo de Volumen: Actual vs. Anterior</flux:heading>
            <flux:subheading>Distribución diaria de llamadas (Atendidas/Abandonadas) comparando semanas.
            </flux:subheading>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-sm bg-green-600"></div>
                <span class="text-xs text-slate-500">Atendidas</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-sm bg-red-600"></div>
                <span class="text-xs text-slate-500">Abandonadas</span>
            </div>
            <flux:separator vertical />
            <div class="flex items-center gap-2">
                <div class="flex flex-col gap-0.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Semanas</span>
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-medium">S1: {{ $volumeComparison['curr_week_label'] }}</span>
                        <span class="text-xs font-medium text-slate-400">S2: {{ $volumeComparison['prev_week_label'] }}</span>
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
                        // Grupo Semana Anterior (S2 - Colores Claros)
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
                        // Grupo Semana Actual (S1 - Colores Sólidos)
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
                        axisBorder: { show: true, color: '#cbd5e1' },
                        axisTicks: { show: true, color: '#cbd5e1' },
                        labels: { style: { colors: '#64748b', fontSize: '12px' } }
                    },
                    yaxis: {
                        labels: { style: { colors: '#64748b', fontSize: '12px' } }
                    },
                    colors: ['#86efac', '#fca5a5', '#16a34a', '#dc2626'],
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '60%',
                            borderRadius: 4,
                        }
                    },
                    dataLabels: { enabled: false },
                    legend: { 
                        position: 'top',
                        labels: { colors: '#64748b' }
                    },
                    grid: {
                        borderColor: '#cbd5e1'
                    },
                    tooltip: { 
                        shared: true, 
                        intersect: false,
                        theme: 'dark'
                    }
                });
                this.chart.render();
            }
        }" x-init="init()" wire:ignore>
        <div x-ref="volumeChart" class="w-full min-h-[320px]"></div>
    </div>
</flux:card>
