<flux:card>
    <flux:heading size="lg" class="mb-6">Distribución de Estados ({{ $this->isHistorical ? 'Histórico' : 'Realtime' }})</flux:heading>

    <div class="relative flex items-center justify-center py-2" x-data="{
            chart: null,
            init() {
                this.chart = new ApexCharts(this.$refs.chart, {
                    chart: {
                        type: 'donut',
                        height: 260,
                        sparkline: { enabled: false },
                        animations: { enabled: true, easing: 'easeinout', speed: 800 }
                    },
                    series: @js(array_values($stateDistribution)),
                    labels: @js(array_keys($stateDistribution)),
                    colors: ['#22c55e', '#3b82f6', '#f59e0b', '#94a3b8'],
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    stroke: { width: 2, colors: ['transparent'] },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '75%',
                                labels: {
                                    show: true,
                                    name: { show: true, offsetY: -10, fontSize: '12px', color: '#a1a1aa' },
                                    value: { show: true, offsetY: 5, fontSize: '20px', fontWeight: 'bold', color: '#ffffff' },
                                    total: {
                                        show: true,
                                        label: 'Agentes',
                                        color: '#a1a1aa',
                                        formatter: function (w) {
                                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                        }
                                    }
                                }
                            }
                        }
                    },
                    tooltip: { enabled: true, theme: 'dark' }
                });
                this.chart.render();
            },
            updateChart() {
                if (this.chart) {
                    this.chart.updateSeries(@js(array_values($stateDistribution)));
                }
            }
        }" x-init="init()" x-effect="updateChart()" wire:ignore>
        <div x-ref="chart" class="w-full min-h-[260px]"></div>
    </div>

    <div class="mt-8 space-y-3">
        @foreach($stateDistribution as $label => $count)
            <div class="flex justify-between items-center text-sm">
                <div class="flex items-center gap-2">
                    <div
                        class="w-3 h-3 rounded-full @if($label === 'Ready') bg-green-500 @elseif($label === 'Talking') bg-blue-500 @elseif($label === 'AUX') bg-amber-500 @else bg-zinc-400 @endif">
                    </div>
                    <span class="text-zinc-600 dark:text-zinc-400">{{ $label }}</span>
                </div>
                <span class="font-mono font-bold">{{ $count }}</span>
            </div>
        @endforeach
    </div>

    <div class="mt-6 pt-4 border-t border-zinc-100 dark:border-zinc-800">
        <div class="flex items-start gap-2">
            <flux:icon name="information-circle" variant="mini" class="text-zinc-400 mt-0.5" />
            <flux:text size="xs" class="text-zinc-500 leading-relaxed italic">
                Contabiliza agentes en cargos de Operador I, II y Coordinadores.
            </flux:text>
        </div>
    </div>
</flux:card>
