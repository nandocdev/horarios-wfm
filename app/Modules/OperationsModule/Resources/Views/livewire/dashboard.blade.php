<div>
    <div wire:poll.{{ $refreshInterval }}s class="space-y-6">
        <x-wfm.page-header :title="$greeting . ', ' . $displayName" :description="$todayLabel" :divider="false">
            <x-slot:actions>
                <x-wfm.live-indicator label="Auto-actualización cada {{ $refreshInterval }}s" color="success" />
                <div class="flex items-center gap-2 text-xs text-white bg-wfm-navy-700 rounded-full px-3 py-1">
                    <span
                        class="status-dot-{{ $operationStatus['state'] === 'critical' ? 'busy' : ($operationStatus['state'] === 'attention' ? 'break' : 'available') }}"></span>
                    {{ $operationStatus['label'] }}
                </div>
            </x-slot:actions>
        </x-wfm.page-header>

        <div class="flex items-center gap-3 text-xs text-wfm-surface-muted px-1">
            <flux:button wire:click="$refresh" variant="ghost" size="sm" icon="arrow-path">Actualizar</flux:button>
            <span>Última sincronización {{ $footer['lastCalculation'] }}</span>
            @if($shift['start'] !== '--:--')
                <span>· Turno {{ $shift['start'] }} - {{ $shift['end'] }}</span>
            @endif
            @if($shift['team'] && $shift['team'] !== '—')
                <span>· {{ $shift['team'] }}</span>
            @endif
            <span class="ml-auto">Semana operativa {{ $weekRange }}</span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            @foreach ($kpis as $kpi)
                        <x-wfm.kpi :value="$kpi['value']" :label="$kpi['label']" :comparison="$kpi['hint'] ?: null" :color="match ($kpi['label']) {
                    'Cobertura' => (float) filter_var($kpi['value'], FILTER_SANITIZE_NUMBER_FLOAT) < 80 ? 'danger' : 'success',
                    'Ausentes' => (int) $kpi['value'] > 0 ? 'warning' : 'success',
                    default => 'default'
                }" />
            @endforeach
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
            <x-wfm.section title="Cobertura durante el día" :description="'Próximo riesgo ' . $nextRisk['time'] . ' · Cobertura esperada ' . $nextRisk['coverage']">
                <x-apex-chart id="coverage-chart" :options="$coverageChartOptions" height="200" />
            </x-wfm.section>

            <x-wfm.section title="Distribución del personal">
                <div class="flex flex-col items-center gap-4 sm:flex-row">
                    <div class="w-36 h-36 flex-shrink-0">
                        <x-apex-chart id="distribution-chart" :options="$donutChartOptions" height="144" />
                    </div>
                    <div class="flex-1 space-y-2 w-full">
                        @php
                            $donutColors = ['#3b82f6', '#22c55e', '#f59e0b', '#94a3b8'];
                            $total = array_sum(array_column($distribution, 'value'));
                        @endphp
                        @foreach($distribution as $i => $d)
                            @php $pct = $total > 0 ? round(($d['value'] / $total) * 100) : 0; @endphp
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                    style="background:{{ $donutColors[$i % count($donutColors)] }}"></span>
                                <span class="flex-1 text-wfm-navy-700">{{ $d['label'] }}</span>
                                <span class="font-semibold text-wfm-navy-800">{{ $d['value'] }}</span>
                                <span class="text-wfm-surface-muted w-8 text-right">{{ $pct }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-wfm.section>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
            <x-wfm.section title="Colas">
                @if(count($queues) > 0)
                    <x-wfm.table :headers="['Cola', 'En Espera', 'Atendidas', 'Abandonadas', 'AHT', '% Abandono', 'SLA', 'Estado']" custom>
                        <tbody class="divide-y divide-wfm-surface-border bg-wfm-surface-card">
                            @foreach ($queues as $queue)
                                <flux:table.row :key="$queue['name']"
                                    class="{{ $queue['state'] === 'critical' ? '!bg-wfm-danger/5' : ($queue['state'] === 'attention' ? '!bg-wfm-warning/5' : '') }}">
                                    <flux:table.cell class="font-medium">{{ $queue['name'] }}</flux:table.cell>
                                    <flux:table.cell class="text-center font-mono">{{ $queue['waiting'] }}</flux:table.cell>
                                    <flux:table.cell class="text-center font-mono">{{ $queue['handled'] }}</flux:table.cell>
                                    <flux:table.cell
                                        class="text-center font-mono {{ $queue['abandoned'] > 0 ? 'text-wfm-danger font-semibold' : '' }}">
                                        {{ $queue['abandoned'] }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-center font-mono">{{ $queue['aht'] }}</flux:table.cell>
                                    <flux:table.cell
                                        class="text-center font-mono {{ $queue['abandon_rate'] > 5 ? 'text-wfm-danger font-semibold' : ($queue['abandon_rate'] > 3 ? 'text-wfm-warning' : '') }}">
                                        {{ $queue['abandon_rate'] }}%
                                    </flux:table.cell>
                                    <flux:table.cell class="text-center font-mono">{{ $queue['sla'] }}</flux:table.cell>
                                    <flux:table.cell class="text-center">
                                        <x-wfm.agent-status
                                            :status="match ($queue['state']) { 'critical' => 'busy', 'attention' => 'break', default => 'available'}"
                                            :label="match ($queue['state']) { 'critical' => 'Crítico', 'attention' => 'Atención', default => 'Normal'}"
                                            size="xs" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </tbody>
                    </x-wfm.table>
                @else
                    <x-wfm.empty icon="queue-list" message="Sin datos de colas en este momento." />
                @endif
            </x-wfm.section>

            <div class="space-y-4">
                <x-wfm.section title="Incidencias Hoy">
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($incidents as $incident)
                            <div class="rounded border border-wfm-surface-border bg-wfm-surface p-3">
                                <p class="text-[10px] kpi-label">{{ $incident['label'] }}</p>
                                <p class="text-lg font-semibold text-wfm-navy-800 dark:text-white mt-1">
                                    {{ $incident['value'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </x-wfm.section>

                <x-wfm.section title="Solicitudes Pendientes">
                    <div class="space-y-2">
                        @foreach ($requests as $request)
                            <div
                                class="flex items-center justify-between rounded border border-wfm-surface-border bg-wfm-surface px-3 py-2">
                                <span class="text-xs font-medium text-wfm-navy-700">{{ $request['label'] }}</span>
                                <span
                                    class="inline-flex items-center justify-center min-w-[1.5rem] h-5 px-1.5 rounded-full bg-wfm-surface-card text-[10px] font-bold text-wfm-navy-700">{{ $request['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-wfm.section>
            </div>
        </div>

        <div class="grid gap-3 xl:grid-cols-[1.1fr_0.9fr]">
            <x-wfm.section title="Eventos Próximos">
                @forelse ($events as $event)
                    <div class="flex gap-3 rounded border border-wfm-surface-border bg-wfm-surface p-2.5 mb-2 last:mb-0">
                        <div
                            class="min-w-[3rem] rounded bg-wfm-surface-card px-2 py-1.5 text-center text-xs font-semibold text-wfm-navy-700">
                            {{ $event['time'] }}
                        </div>
                        <div>
                            <p class="text-xs font-medium text-wfm-navy-800 dark:text-white">{{ $event['title'] }}</p>
                            <p class="text-[10px] text-wfm-surface-muted mt-0.5">{{ $event['detail'] }}</p>
                        </div>
                    </div>
                @empty
                    <x-wfm.empty icon="calendar" message="Sin eventos próximos." />
                @endforelse
            </x-wfm.section>

            <div class="space-y-4">
                <x-wfm.section title="Ranking Equipos">
                    @forelse ($teams as $team)
                        <div class="mb-3 last:mb-0">
                            <div class="flex items-center justify-between text-xs text-wfm-surface-muted mb-1">
                                <span>{{ $team['name'] }}</span>
                                <span class="font-semibold text-wfm-navy-800">{{ $team['value'] }}%</span>
                            </div>
                            <div class="w-full bg-wfm-surface rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-wfm-info" style="width: {{ $team['value'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <x-wfm.empty icon="users" message="Sin datos de equipos." />
                    @endforelse
                </x-wfm.section>

                <x-wfm.alert-list :alerts="$alerts->toArray()" title="Alertas Operativas" />
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
            <x-wfm.section title="Tendencia Semanal">
                @forelse ($trends as $trend)
                    @php
                        $sparkData = $trend['data'] ?? [];
                        $sparkVals = array_values($sparkData);
                        $lastVal = end($sparkVals);
                        $prevVal = $sparkVals[count($sparkVals) - 2] ?? 0;
                        $diff = $lastVal - $prevVal;
                        $dir = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat');
                        $chartId = 'spark-' . \Illuminate\Support\Str::slug($trend['label']);
                    @endphp
                    <div class="mb-4 last:mb-0">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-wfm-navy-700">{{ $trend['label'] }}</span>
                            @if(count($sparkData) > 1)
                                <span
                                    class="text-[10px] {{ $dir === 'up' ? 'text-wfm-success' : ($dir === 'down' ? 'text-wfm-danger' : 'text-wfm-surface-muted') }}">
                                    @if($dir === 'up')▲ @elseif($dir === 'down')▼ @endif {{ number_format(abs($diff)) }}
                                </span>
                            @endif
                        </div>
                        <x-apex-chart :id="$chartId" :options="$sparklineOptions[$trend['label']] ?? '{}'" height="40" />
                    </div>
                @empty
                    <x-wfm.empty icon="chart-bar" message="Sin tendencias disponibles." />
                @endforelse
            </x-wfm.section>

            <x-wfm.section title="Acciones Rápidas">
                <div class="flex flex-wrap gap-2">
                    @foreach ($quickActions as $action)
                        <flux:button variant="ghost" size="sm" href="{{ route($action['route']) }}" wire:navigate>
                            {{ $action['label'] }}
                        </flux:button>
                    @endforeach
                </div>
            </x-wfm.section>
        </div>

        <x-wfm.section>
            <div class="flex flex-wrap gap-4 text-xs text-wfm-surface-muted">
                <span><span class="font-semibold text-wfm-navy-700">Usuarios conectados</span>
                    {{ $footer['connectedUsers'] }}</span>
                <span><span class="font-semibold text-wfm-navy-700">Último cálculo</span>
                    {{ $footer['lastCalculation'] }}</span>
                <span><span class="font-semibold text-wfm-navy-700">Última publicación</span>
                    {{ $footer['lastSchedulesPublished'] }}</span>
                <span><span class="font-semibold text-wfm-navy-700">Próxima actualización</span>
                    {{ $footer['nextRefresh'] }}</span>
            </div>
        </x-wfm.section>
    </div>
</div>