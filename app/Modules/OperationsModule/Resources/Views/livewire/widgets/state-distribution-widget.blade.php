<flux:card>
    <flux:heading size="lg" class="mb-8">Distribución de Estados ({{ $this->isHistorical ? 'Histórico' : 'Realtime' }})</flux:heading>

    @php
        $apexChartOptions = [
            'chart' => [
                'type' => 'donut',
                'height' => 260,
                'sparkline' => ['enabled' => false],
                'animations' => ['enabled' => true, 'easing' => 'easeinout', 'speed' => 800],
            ],
            'series' => array_values($stateDistribution),
            'labels' => array_keys($stateDistribution),
            'colors' => ['#22c55e', '#3b82f6', '#f59e0b', '#94a3b8'],
            'dataLabels' => ['enabled' => false],
            'legend' => ['show' => false],
            'stroke' => ['width' => 2, 'colors' => ['transparent']],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '75%',
                        'labels' => [
                            'show' => true,
                            'name' => ['show' => true, 'offsetY' => -10, 'fontSize' => '12px', 'color' => '#64748b'],
                            'value' => ['show' => true, 'offsetY' => 5, 'fontSize' => '20px', 'fontWeight' => 'bold', 'color' => '#0f172a'],
                            'total' => ['show' => true, 'label' => 'Agentes', 'color' => '#64748b', 'formatter' => ['__callback' => 'agentTotal']],
                        ],
                    ],
                ],
            ],
            'tooltip' => ['enabled' => true, 'theme' => 'dark'],
        ];
    @endphp
    <div class="relative flex items-center justify-center py-2">
        <x-apex-chart id="state-distribution-chart" :options="$apexChartOptions" height="260px" />
    </div>

    <div class="mt-8 space-y-2">
        @foreach($stateDistribution as $label => $count)
            <div class="flex justify-between items-center text-sm">
                <div class="flex items-center gap-2">
                    <div
                        class="w-3 h-3 rounded-sm @if($label === 'Ready') bg-green-600 @elseif($label === 'Talking') bg-blue-600 @elseif($label === 'AUX') bg-amber-500 @else bg-slate-400 @endif">
                    </div>
                    <span class="text-slate-600 dark:text-slate-400">{{ $label }}</span>
                </div>
                <span class="font-mono font-semibold">{{ $count }}</span>
            </div>
        @endforeach
    </div>

    <div class="mt-8 pt-4 border-t border-slate-200 dark:border-slate-800">
        <div class="flex items-start gap-2">
            <flux:icon name="information-circle" variant="mini" class="text-slate-400 mt-0.5" />
            <flux:text size="xs" class="text-slate-500 leading-relaxed italic">
                Contabiliza agentes en cargos de Operador I, II y Coordinadores.
            </flux:text>
        </div>
    </div>
</flux:card>
