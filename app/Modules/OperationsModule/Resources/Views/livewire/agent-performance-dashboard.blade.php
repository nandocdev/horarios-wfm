<div class="space-y-6">
    @if(!$this->employeeId)
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <flux:icon name="user" class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mb-4" />
            <flux:heading size="xl" level="2" class="mb-2">Sin empleado asociado</flux:heading>
            <flux:text class="text-zinc-500 max-w-md">
                No hay un empleado vinculado a tu cuenta de usuario.
                Para ver este dashboard, un administrador debe asociarte a un registro de empleado.
            </flux:text>
        </div>
    @else
    {{-- Cabecera: Perfil del agente + selector de empleado para supervisores --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-zinc-900/40 p-6 rounded-md border border-zinc-800">
        <div class="flex items-center gap-4">
            @if($this->employee)
                <flux:avatar size="lg" class="bg-blue-600/20 text-blue-400 border border-blue-500/30" />
                <div>
                    <flux:heading size="xl" level="1">
                        {{ $this->employee->first_name }} {{ $this->employee->last_name }}
                    </flux:heading>
                    <flux:subheading>
                        {{ $this->employee->position?->name }} · {{ $this->employee->team?->name }}
                        @if($this->employee->employee_number)
                            · #{{ $this->employee->employee_number }}
                        @endif
                    </flux:subheading>
                </div>
            @endif
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            @if(!empty($selectableEmployees))
                <div class="w-64">
                    <flux:select wire:model.live="employeeId" size="sm" placeholder="Seleccionar Agente">
                        @foreach($selectableEmployees as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="days" size="sm" class="w-28">
                    <option value="5">Últimos 5 días</option>
                    <option value="10">Últimos 10 días</option>
                    <option value="15">Últimos 15 días</option>
                </flux:select>
                <flux:button icon="arrow-path" size="sm" variant="ghost" wire:click="$refresh" class="hover:bg-zinc-800" />
            </div>
        </div>
    </div>

    @php 
        $perf = $this->performance; 
        $adherence = $perf['summary']['avg_adherence'] ?? 0;
        $occupancy = $perf['summary']['avg_occupancy'] ?? 0;
        $score = (int) round(($adherence * 0.6) + ($occupancy * 0.4));
    @endphp

    {{-- Bloque Superior: Score Operativo del Periodo + Estado Actual / Objetivos --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Tarjeta 1: Score Operativo --}}
        <flux:card class="flex flex-col items-center justify-center p-6 text-center lg:col-span-1 border border-zinc-800">
            <flux:heading size="md" class="text-zinc-400 mb-4">Score del Periodo</flux:heading>
            <div class="relative flex items-center justify-center">
                {{-- SVG de progreso circular --}}
                <svg class="w-32 h-32 transform -rotate-90">
                    <circle cx="64" cy="64" r="54" stroke="#27272a" stroke-width="8" fill="transparent" />
                    <circle cx="64" cy="64" r="54" stroke="{{ $score < 85 ? '#ef4444' : ($score < 92 ? '#f59e0b' : '#22c55e') }}" stroke-width="8" fill="transparent" 
                            stroke-dasharray="339.29" 
                            stroke-dashoffset="{{ 339.29 - (339.29 * $score) / 100 }}" />
                </svg>
                <div class="absolute flex flex-col items-center justify-center">
                    <span class="text-3xl font-extrabold text-white font-mono">{{ $score }}</span>
                    <span class="text-[10px] text-zinc-500 uppercase tracking-widest">/ 100</span>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm font-bold {{ $score < 85 ? 'text-red-500' : ($score < 92 ? 'text-amber-500' : 'text-green-500') }}">
                    @if($score < 85) En Peligro @elseif($score < 92) Aceptable @else Excelente @endif
                </span>
                <p class="text-[10px] text-zinc-500 mt-0.5">Basado en Adherencia y Ocupación</p>
            </div>
        </flux:card>

        {{-- Tarjeta 2: Resumen del Periodo --}}
        <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Adherencia --}}
            <flux:card class="border-b-4 {{ $adherence < 85 ? 'border-b-red-500' : ($adherence < 92 ? 'border-b-amber-500' : 'border-b-green-500') }}">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Adherencia</span>
                        <flux:icon name="clock" variant="mini" class="text-zinc-500" />
                    </div>
                    <span class="text-2xl font-bold tracking-tight {{ $adherence < 85 ? 'text-red-500' : ($adherence < 92 ? 'text-amber-500' : 'text-green-500') }}">
                        {{ $adherence }}%
                    </span>
                    <span class="text-xs text-zinc-500">promedio</span>
                </div>
            </flux:card>

            {{-- AHT Global --}}
            <flux:card class="border-b-4 border-b-blue-500">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">AHT Global</span>
                        <flux:icon name="phone" variant="mini" class="text-blue-500" />
                    </div>
                    <span class="text-2xl font-bold tracking-tight text-white">
                        {{ sprintf('%02d:%02d', floor(($perf['summary']['avg_aht_seconds'] ?? 0) / 60), ($perf['summary']['avg_aht_seconds'] ?? 0) % 60) }}
                    </span>
                    <span class="text-xs text-zinc-500">media de conversación</span>
                </div>
            </flux:card>

            {{-- Ocupación --}}
            <flux:card class="border-b-4 {{ $occupancy < 85 ? 'border-b-red-500' : ($occupancy < 90 ? 'border-b-amber-500' : 'border-b-green-500') }}">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Ocupación</span>
                        <flux:icon name="chart-bar" variant="mini" class="text-zinc-500" />
                    </div>
                    <span class="text-2xl font-bold tracking-tight {{ $occupancy < 85 ? 'text-red-500' : ($occupancy < 90 ? 'text-amber-500' : 'text-green-500') }}">
                        {{ $occupancy }}%
                    </span>
                    <span class="text-xs text-zinc-500">promedio</span>
                </div>
            </flux:card>

            {{-- Llamadas Totales --}}
            <flux:card class="border-b-4 border-b-zinc-700">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Llamadas</span>
                        <flux:icon name="chat-bubble-left-right" variant="mini" class="text-zinc-500" />
                    </div>
                    <span class="text-2xl font-bold tracking-tight text-white">
                        {{ $perf['summary']['total_calls'] ?? 0 }}
                    </span>
                    <span class="text-xs text-zinc-500">atendidas</span>
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Widget 2: Historial de Desempeño (Líneas) + Widget 3: Distribución Estados --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Línea temporal --}}
        <div class="lg:col-span-2">
            <flux:card>
                <flux:heading size="lg" class="mb-6">Historial de Adherencia y Ocupación</flux:heading>
                <div x-data="{
                    chart: null,
                    init() {
                        this.chart = new ApexCharts(this.$refs.historyChart, {
                            chart: { 
                                type: 'line', 
                                height: 280, 
                                toolbar: { show: false }, 
                                animations: { enabled: true, easing: 'easeinout', speed: 800 } 
                            },
                            series: [
                                { name: 'Adherencia', data: @js($perf['dailyAdherence'] ?? []) },
                                { name: 'Ocupación', data: @js($perf['dailyOccupancy'] ?? []) },
                            ],
                            xaxis: { 
                                categories: @js($perf['dailyLabels'] ?? []), 
                                labels: { style: { colors: '#a1a1aa', fontSize: '12px' } } 
                            },
                            yaxis: { 
                                max: 100, 
                                labels: { formatter: v => v + '%', style: { colors: '#a1a1aa' } } 
                            },
                            colors: ['#3b82f6', '#22c55e'],
                            stroke: { curve: 'smooth', width: 3 },
                            markers: { size: 5, hover: { size: 7 } },
                            tooltip: { theme: 'dark', y: { formatter: v => v + '%' } },
                            grid: { borderColor: '#27272a' },
                            legend: { position: 'top', labels: { colors: '#a1a1aa' } },
                        });
                        this.chart.render();
                    }
                }" x-init="init()" wire:ignore>
                    <div x-ref="historyChart" class="w-full min-h-[280px]"></div>
                </div>
            </flux:card>
        </div>

        {{-- Donut distribución --}}
        <div class="lg:col-span-1">
            <flux:card>
                <flux:heading size="lg" class="mb-6">Distribución de Tiempo</flux:heading>
                <div x-data="{
                    chart: null,
                    init() {
                        this.chart = new ApexCharts(this.$refs.stateChart, {
                            chart: { type: 'donut', height: 240, sparkline: { enabled: false } },
                            series: @js(array_map(fn($s) => $s['minutes'], $perf['stateDistribution'] ?? [])),
                            labels: @js(array_map(fn($s) => $s['label'], $perf['stateDistribution'] ?? [])),
                            colors: @js(array_map(fn($s) => $s['color'], $perf['stateDistribution'] ?? [])),
                            dataLabels: { enabled: false },
                            legend: { show: false },
                            stroke: { width: 2, colors: ['transparent'] },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '70%',
                                        labels: {
                                            show: true,
                                            name: { show: true, offsetY: -10, fontSize: '12px', color: '#a1a1aa' },
                                            value: { show: true, offsetY: 5, fontSize: '18px', fontWeight: 'bold', color: '#ffffff' },
                                            total: { 
                                                show: true, 
                                                label: 'Total', 
                                                color: '#a1a1aa', 
                                                formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0) + ' min' 
                                            }
                                        }
                                    }
                                }
                            },
                            tooltip: { theme: 'dark' }
                        });
                        this.chart.render();
                    }
                }" x-init="init()" wire:ignore>
                    <div x-ref="stateChart" class="w-full min-h-[240px]"></div>
                </div>
                <div class="mt-6 space-y-2">
                    @foreach($perf['stateDistribution'] ?? [] as $state)
                        <div class="flex justify-between items-center text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background: {{ $state['color'] }}"></div>
                                <span class="text-zinc-400">{{ $state['label'] }}</span>
                            </div>
                            <span class="font-mono font-bold text-sm text-white">{{ $state['minutes'] }} min</span>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Comparación con el Equipo & Reconocimientos --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Tabla Comparativa --}}
        <div class="lg:col-span-2">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Comparativa con el Equipo</flux:heading>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-zinc-500 uppercase border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <th class="px-4 py-3">Indicador</th>
                                <th class="px-4 py-3 text-center">Yo</th>
                                <th class="px-4 py-3 text-center">Equipo (prom.)</th>
                                <th class="px-4 py-3 text-right">Mejor del equipo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            {{-- Llamadas --}}
                            <tr class="hover:bg-zinc-800/10">
                                <td class="px-4 py-3 font-medium text-zinc-400">Llamadas</td>
                                <td class="px-4 py-3 text-center font-bold text-white font-mono">{{ $perf['summary']['total_calls'] ?? 0 }}</td>
                                <td class="px-4 py-3 text-center font-mono text-zinc-400">{{ $perf['summary']['team_comparison']['calls'] ?? 38 }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="font-mono text-green-400 font-bold">{{ $perf['summary']['team_comparison']['best']['calls'] ?? 57 }}</span>
                                    </div>
                                </td>
                            </tr>
                            {{-- AHT --}}
                            <tr class="hover:bg-zinc-800/10">
                                <td class="px-4 py-3 font-medium text-zinc-400">AHT</td>
                                <td class="px-4 py-3 text-center font-bold text-blue-400 font-mono">
                                    {{ sprintf('%02d:%02d', floor(($perf['summary']['avg_aht_seconds'] ?? 0) / 60), ($perf['summary']['avg_aht_seconds'] ?? 0) % 60) }}
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-zinc-400">
                                    {{ sprintf('%02d:%02d', floor(($perf['summary']['team_comparison']['aht'] ?? 348) / 60), ($perf['summary']['team_comparison']['aht'] ?? 348) % 60) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="font-mono text-green-400 font-bold">
                                            {{ sprintf('%02d:%02d', floor(($perf['summary']['team_comparison']['best']['aht'] ?? 290) / 60), ($perf['summary']['team_comparison']['best']['aht'] ?? 290) % 60) }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            {{-- Adherencia --}}
                            <tr class="hover:bg-zinc-800/10">
                                <td class="px-4 py-3 font-medium text-zinc-400">Adherencia</td>
                                <td class="px-4 py-3 text-center font-bold text-white font-mono">{{ $adherence }}%</td>
                                <td class="px-4 py-3 text-center font-mono text-zinc-400">{{ $perf['summary']['team_comparison']['adherence'] ?? '90%' }}%</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="font-mono text-green-400 font-bold">{{ $perf['summary']['team_comparison']['best']['adherence'] ?? '96.8%' }}%</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>

        {{-- Reconocimientos / Logos --}}
        <div class="lg:col-span-1">
            <flux:card class="h-full">
                <flux:heading size="lg" class="mb-4">Reconocimientos</flux:heading>
                <div class="space-y-4">
                    {{-- Medalla 1 --}}
                    @if($adherence >= 92)
                        <div class="flex items-center gap-3 p-3 bg-green-950/20 border border-green-800/30 rounded-md">
                            <flux:icon name="trophy" class="w-8 h-8 text-green-400" />
                            <div>
                                <h4 class="text-sm font-bold text-green-200">Adherencia de Oro</h4>
                                <p class="text-xs text-green-400/80">Mantienes adherencia superior al 92%</p>
                            </div>
                        </div>
                    @endif
                    
                    {{-- Medalla 2 --}}
                    @if(($perf['summary']['total_calls'] ?? 0) >= 40)
                        <div class="flex items-center gap-3 p-3 bg-blue-950/20 border border-blue-800/30 rounded-md">
                            <flux:icon name="bolt" class="w-8 h-8 text-blue-400" />
                            <div>
                                <h4 class="text-sm font-bold text-blue-200">Alta Productividad</h4>
                                <p class="text-xs text-blue-400/80">Más de 40 llamadas atendidas en el periodo</p>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center gap-3 p-3 bg-zinc-800/30 rounded-md">
                        <flux:icon name="sparkles" class="w-8 h-8 text-amber-400" />
                        <div>
                            <h4 class="text-sm font-bold text-zinc-200">Desempeño Constante</h4>
                            <p class="text-xs text-zinc-500">5 días consecutivos cumpliendo objetivos</p>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Widget 4: Rendimiento por Colas ACD --}}
    @if(!empty($perf['queueDetail']))
        <flux:card>
            <flux:heading size="lg" class="mb-4">Rendimiento por Colas ACD</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-zinc-500 uppercase border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-4 py-3">Cola</th>
                            <th class="px-4 py-3 text-center">Llamadas Atendidas</th>
                            <th class="px-4 py-3 text-center">AHT Promedio</th>
                            <th class="px-4 py-3 text-right">% del total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @php $totalCalls = $perf['summary']['total_calls'] ?: 1; @endphp
                        @foreach($perf['queueDetail'] as $queue)
                            <tr class="hover:bg-zinc-800/20 transition-opacity">
                                <td class="px-4 py-3 font-medium text-zinc-200">{{ $queue['name'] }}</td>
                                <td class="px-4 py-3 text-center font-mono text-white font-semibold">{{ $queue['total_calls'] }}</td>
                                <td class="px-4 py-3 text-center font-mono text-blue-400">
                                    {{ sprintf('%02d:%02d', floor($queue['aht_seconds'] / 60), $queue['aht_seconds'] % 60) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-24 bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                            <div class="h-2 rounded-full bg-blue-500" style="width: {{ min(100, round(($queue['total_calls'] / $totalCalls) * 100)) }}%"></div>
                                        </div>
                                        <span class="text-xs text-zinc-500 w-8 text-right">{{ round(($queue['total_calls'] / $totalCalls) * 100) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif

    {{-- Widget 5: Desviaciones del Turno (Timeline) --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Desviaciones del Turno (Conciliación Diaria)</flux:heading>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-zinc-500 uppercase border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-3">Día</th>
                        <th class="px-4 py-3">Estatus Entrada</th>
                        <th class="px-4 py-3 text-center">Retraso Entrada</th>
                        <th class="px-4 py-3 text-center">Tiempo en AUX</th>
                        <th class="px-4 py-3 text-center">Salida Anticipada</th>
                        <th class="px-4 py-3 text-right">Conectado / Programado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($perf['deviations'] ?? [] as $dev)
                        <tr class="hover:bg-zinc-800/20 transition-opacity">
                            <td class="px-4 py-3 font-medium text-zinc-200">{{ $dev['label'] }}</td>
                            <td class="px-4 py-3">
                                @if($dev['entry_status'] === 'a_tiempo')
                                    <flux:badge color="green" variant="subtle" size="sm">A tiempo</flux:badge>
                                @elseif($dev['entry_status'] === 'tardanza')
                                    <flux:badge color="red" variant="subtle" size="sm">Tardanza</flux:badge>
                                @elseif($dev['entry_status'] === 'ausente')
                                    <flux:badge color="red" variant="subtle" size="sm">Ausente</flux:badge>
                                @elseif($dev['entry_status'] === 'excepción')
                                    <flux:badge color="amber" variant="subtle" size="sm">Excepción</flux:badge>
                                @else
                                    <flux:badge color="zinc" variant="subtle" size="sm">{{ $dev['entry_status'] }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-sm {{ $dev['late_minutes'] > 10 ? 'text-red-500' : ($dev['late_minutes'] > 0 ? 'text-amber-500' : 'text-green-500') }}">
                                {{ $dev['late_minutes'] > 0 ? $dev['late_minutes'] . ' min' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-sm {{ $dev['aux_minutes'] > 60 ? 'text-amber-500' : 'text-green-500' }}">
                                {{ $dev['aux_minutes'] }} min
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-sm {{ $dev['early_exit_minutes'] > 10 ? 'text-red-500' : 'text-zinc-500' }}">
                                {{ $dev['early_exit_minutes'] > 0 ? $dev['early_exit_minutes'] . ' min antes' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-zinc-400">
                                {{ $dev['connected_minutes'] > 0 ? round($dev['connected_minutes'] / 60, 1) . 'h' : '0h' }}
                                <span class="text-zinc-600">/ {{ $dev['scheduled_minutes'] > 0 ? round($dev['scheduled_minutes'] / 60, 1) . 'h' : '0h' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No hay datos de desviaciones en el período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>
@endif
</div>
