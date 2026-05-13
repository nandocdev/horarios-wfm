<div wire:poll.{{ $refreshInterval }}s class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Dashboard Operativo</flux:heading>
            <flux:subheading>Estado global de la operación en tiempo real.</flux:subheading>
        </div>

        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 text-sm text-zinc-500">
                <span class="relative flex h-2 w-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                Live
            </div>
            <flux:button icon="arrow-path" size="sm" variant="ghost" wire:click="$refresh" />
        </div>
    </div>

    {{-- Row 1: Hero KPIs --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 xl:grid-cols-6 gap-4">
        @foreach($this->heroKpis as $kpi)
            <flux:card class="relative overflow-hidden">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ $kpi['label'] }}</span>
                        <flux:icon :name="$kpi['icon']" variant="mini" class="text-zinc-400" />
                    </div>

                    <div class="flex items-baseline gap-2 mt-1">
                        <span
                            class="text-2xl font-bold tracking-tight @if($kpi['status'] === 'danger') text-red-600 @elseif($kpi['status'] === 'warning') text-amber-600 @elseif($kpi['status'] === 'success') text-green-600 @else text-zinc-900 dark:text-white @endif">
                            {{ $kpi['value'] }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1 mt-1">
                        <span
                            class="text-xs font-medium {{ str_contains($kpi['delta'], '+') ? 'text-green-600' : (str_contains($kpi['delta'], '-') ? 'text-red-600' : 'text-zinc-400') }}">
                            {{ $kpi['delta'] }}
                        </span>
                        <span class="text-[10px] text-zinc-400">vs ayer</span>
                    </div>
                </div>

                {{-- Mini sparkline placeholder color background --}}
                <div
                    class="absolute bottom-0 left-0 right-0 h-1 @if($kpi['status'] === 'danger') bg-red-500/20 @elseif($kpi['status'] === 'warning') bg-amber-500/20 @elseif($kpi['status'] === 'success') bg-green-500/20 @else bg-zinc-500/10 @endif">
                </div>
            </flux:card>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Row 2: Queue State & Distribution --}}
        <div class="lg:col-span-2">
            <flux:card>
                <div class="flex items-center justify-between mb-6">
                    <flux:heading size="lg">Estado de Colas (Realtime)</flux:heading>
                    <flux:badge variant="subtle">ACD Data</flux:badge>
                </div>

                <div class="space-y-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead
                                class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <th class="px-4 py-3">Cola</th>
                                    <th class="px-4 py-3 text-center">Espera</th>
                                    <th class="px-4 py-3 text-center">Hablando</th>
                                    <th class="px-4 py-3 text-center text-blue-600">Recibidas</th>
                                    <th class="px-4 py-3 text-center text-green-600">Atendidas</th>
                                    <th class="px-4 py-3 text-center text-red-600">Aband.</th>
                                    <th class="px-4 py-3 text-center">T. Máx. Espera</th>
                                    <th class="px-4 py-3 text-center">SL %</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @forelse($this->queueStats as $queue)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $queue['name'] }}
                                        </td>
                                        <td class="px-4 py-3 text-center font-bold">
                                            <flux:badge
                                                :color="$queue['status'] === 'danger' ? 'red' : ($queue['status'] === 'warning' ? 'amber' : null)"
                                                variant="subtle">
                                                {{ $queue['waiting'] }}
                                            </flux:badge>
                                        </td>
                                        <td class="px-4 py-3 text-center text-blue-600 font-bold">
                                            {{ $queue['talking'] }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-zinc-600 dark:text-zinc-400">
                                            {{ $queue['received'] }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-green-600 dark:text-green-400 font-medium">
                                            {{ $queue['handled'] }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-red-600 dark:text-red-400 font-medium">
                                            {{ $queue['abandoned'] }}
                                        </td>
                                        <td class="px-4 py-3 text-center font-mono text-xs text-zinc-500">
                                            {{ sprintf('%02d:%02d', floor($queue['lwt'] / 60), $queue['lwt'] % 60) }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <flux:badge :color="$queue['sl'] < 80 ? 'red' : ($queue['sl'] < 90 ? 'amber' : 'green')" variant="outline" size="sm">
                                                {{ round($queue['sl'], 0) }}%
                                            </flux:badge>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500">No hay actividad en
                                            colas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </flux:card>
        </div>

        <div class="lg:col-span-1">
            <flux:card>
                <flux:heading size="lg" class="mb-6">Distribución de Estados</flux:heading>

                {{-- Donut Chart Placeholder (ApexCharts se cargaría aquí) --}}
                <div class="relative flex items-center justify-center py-6">
                    <div class="w-40 h-40 rounded-full border-[12px] border-zinc-100 flex items-center justify-center">
                        <div class="text-center">
                            <flux:text size="xl" class="font-bold">{{ array_sum($this->stateDistribution) }}</flux:text>
                            <flux:text size="sm" variant="subtle">Total</flux:text>
                        </div>
                    </div>
                </div>

                <div class="mt-8 space-y-3">
                    @foreach($this->stateDistribution as $label => $count)
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
            </flux:card>
        </div>
    </div>

    {{-- Row 3: Administrative & Alerts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Alertas Críticas</flux:heading>
                <div class="space-y-3">
                    @if($this->pendingApprovals > 0)
                        <div class="p-3 bg-blue-50 dark:bg-blue-950/20 border-l-4 border-blue-500 flex items-start gap-3">
                            <flux:icon name="clock" class="text-blue-500 mt-0.5" />
                            <div>
                                <flux:text size="sm" class="font-bold text-blue-900 dark:text-blue-200">Pendientes de
                                    Aprobación</flux:text>
                                <flux:text size="sm" class="text-blue-700 dark:text-blue-300">Hay
                                    {{ $this->pendingApprovals }} solicitudes esperando revisión de WFM.
                                </flux:text>
                            </div>
                        </div>
                    @endif

                    @foreach($this->queueStats as $queue)
                        @if($queue['status'] === 'danger')
                            <div class="p-3 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 flex items-start gap-3">
                                <flux:icon name="exclamation-triangle" class="text-red-500 mt-0.5" />
                                <div>
                                    <flux:text size="sm" class="font-bold text-red-900 dark:text-red-200">SLA Crítico:
                                        {{ $queue['name'] }}
                                    </flux:text>
                                    <flux:text size="sm" class="text-red-700 dark:text-red-300">Llamadas en espera:
                                        {{ $queue['waiting'] }}. Nivel de servicio al {{ $queue['sl'] }}%.
                                    </flux:text>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </flux:card>
        </div>

        <div class="lg:col-span-2">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Últimas Incidencias de Asistencia</flux:heading>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-zinc-500 uppercase">
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <th class="px-4 py-2">Agente</th>
                                <th class="px-4 py-2">Tipo</th>
                                <th class="px-4 py-2">Hora</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse($this->recentIncidents as $incident)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $incident->first_name }} {{ $incident->last_name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <flux:badge size="sm" variant="pill">{{ $incident->type }}</flux:badge>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500 text-xs">
                                        {{ \Carbon\Carbon::parse($incident->created_at)->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-zinc-500">No hay incidencias
                                        recientes.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>
    </div>
</div>