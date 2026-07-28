<div class="space-y-4">
    {{-- Team Selector --}}
    <div class="flex flex-wrap items-center justify-between gap-3 p-4 bg-white dark:bg-zinc-900 border border-wfm-surface-border rounded-xl">
        <div class="flex items-center gap-3">
            <flux:select wire:model.live="teamId" placeholder="Seleccionar equipo" class="w-64">
                @foreach($teams as $team)
                    <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="selectedDate" class="w-44">
                @for($i = 0; $i <= 6; $i++)
                    @php $d = now()->subDays($i); @endphp
                    <flux:select.option value="{{ $d->toDateString() }}">{{ $i === 0 ? 'Hoy' : $d->locale('es')->translatedFormat('l d F') }}</flux:select.option>
                @endfor
            </flux:select>
        </div>
    </div>

    @if(!$teamId)
        <x-wfm.empty icon="users" message="Seleccione un equipo para ver su dashboard" />
    @else
        @php $h = $headcount; @endphp

        {{-- Headcount --}}
        <div class="p-4 bg-white dark:bg-zinc-900 border border-wfm-surface-border rounded-xl">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-wfm-navy-800 dark:text-white uppercase tracking-wider">Headcount del Equipo</span>
                <span class="text-xs text-wfm-surface-muted">Shrinkage: {{ $h['shrinkage'] ?? 0 }}%</span>
            </div>
            @php
                $total = max($h['total'] ?? 1, 1);
                $pPct = (($h['present'] ?? 0) / $total) * 100;
                $vPct = (($h['vacation'] ?? 0) / $total) * 100;
                $lPct = (($h['leave'] ?? 0) / $total) * 100;
                $aPct = (($h['absent'] ?? 0) / $total) * 100;
                $sPct = (($h['swap'] ?? 0) / $total) * 100;
            @endphp
            <div class="flex h-6 rounded-full overflow-hidden mb-2">
                <div class="bg-wfm-success transition-all" style="width: {{ $pPct }}%" title="Presentes: {{ $h['present'] ?? 0 }}"></div>
                <div class="bg-blue-400 transition-all" style="width: {{ $vPct }}%" title="Vacaciones: {{ $h['vacation'] ?? 0 }}"></div>
                <div class="bg-amber-400 transition-all" style="width: {{ $lPct }}%" title="Permisos: {{ $h['leave'] ?? 0 }}"></div>
                <div class="bg-wfm-danger transition-all" style="width: {{ $aPct }}%" title="Ausentes: {{ $h['absent'] ?? 0 }}"></div>
                <div class="bg-purple-400 transition-all" style="width: {{ $sPct }}%" title="Cambio Turno: {{ $h['swap'] ?? 0 }}"></div>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-wfm-surface-muted">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded inline-block bg-wfm-success"></span> Presentes {{ $h['present'] ?? 0 }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded inline-block bg-blue-400"></span> Vacaciones {{ $h['vacation'] ?? 0 }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded inline-block bg-amber-400"></span> Permisos {{ $h['leave'] ?? 0 }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded inline-block bg-wfm-danger"></span> Ausentes {{ $h['absent'] ?? 0 }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded inline-block bg-purple-400"></span> Cambio Turno {{ $h['swap'] ?? 0 }}</span>
                <span class="font-semibold">Asignados: {{ $h['total'] ?? 0 }}</span>
            </div>
        </div>

        {{-- KPIs --}}
        @php $k = $teamKpis; @endphp
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <x-wfm.kpi :value="$k['avg_aht'] ?? 0 . 's'" label="AHT Promedio" icon="clock" color="info" />
            <x-wfm.kpi :value="$k['total_calls'] ?? 0" label="Llamadas" icon="phone" color="success" />
            <x-wfm.kpi :value="($k['sla'] ?? 0) . '%'" label="SLA Equipo" icon="check-badge" :color="($k['sla'] ?? 0) >= 80 ? 'success' : 'danger'" />
            <x-wfm.kpi :value="($k['avg_productivity'] ?? 0) . '%'" label="Productividad" icon="chart-pie" color="info" />
            <x-wfm.kpi :value="($k['avg_occupancy'] ?? 0) . '%'" label="Ocupación" icon="cpu-chip" />
            <x-wfm.kpi :value="($k['connected_count'] ?? 0) . '/' . ($k['total_count'] ?? 0)" label="Conectados" icon="signal" color="success" />
        </div>

        {{-- Live States + Alerts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <x-wfm.section title="Estados en Vivo">
                @php
                    $sd = $stateDistribution;
                    $colorMap = ['TALKING' => 'bg-wfm-info', 'READY' => 'bg-wfm-success',
                        'NOT_READY' => 'bg-amber-400', 'WORK' => 'bg-purple-500',
                        'RESERVED' => 'bg-cyan-500', 'OFFLINE' => 'bg-wfm-danger',
                        'NOT_READY_LUNCH' => 'bg-orange-400', 'NOT_READY_BREAK' => 'bg-yellow-400'];
                    $labels = ['TALKING' => 'Hablando', 'READY' => 'Ready',
                        'NOT_READY' => 'Auxiliar', 'WORK' => 'ACW',
                        'RESERVED' => 'Reserved', 'OFFLINE' => 'Offline',
                        'NOT_READY_LUNCH' => 'Almuerzo', 'NOT_READY_BREAK' => 'Descanso'];
                    $totalStates = array_sum($sd) ?: 1;
                @endphp
                <div class="flex items-end gap-1 h-24">
                    @foreach(['TALKING', 'READY', 'NOT_READY', 'WORK', 'RESERVED', 'NOT_READY_LUNCH', 'NOT_READY_BREAK', 'OFFLINE'] as $key)
                        @php
                            $val = $sd[$key] ?? 0;
                            $pct = ($val / $totalStates) * 100;
                            $hPx = max(4, ($val / max(max($sd), 1)) * 80);
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <span class="text-[9px] font-mono text-wfm-surface-muted">{{ $val }}</span>
                            <div class="w-full rounded-sm {{ $colorMap[$key] }}" style="height: {{ $hPx }}px; min-height: 4px;"></div>
                            <span class="text-[9px] text-wfm-surface-muted">{{ $labels[$key] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-wfm.section>

            <x-wfm.section title="Alertas Activas">
                <div class="space-y-1.5 max-h-48 overflow-y-auto">
                    @forelse($alerts as $alert)
                        <div class="flex items-start gap-2 p-2 rounded text-xs {{ $alert['level'] === 'danger' ? 'bg-wfm-danger/5 border border-wfm-danger/20' : 'bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/20' }}">
                            <span class="mt-0.5">
                                @if($alert['level'] === 'danger')
                                    <flux:icon.exclamation-circle class="w-3.5 h-3.5 text-wfm-danger" />
                                @else
                                    <flux:icon.exclamation-triangle class="w-3.5 h-3.5 text-amber-500" />
                                @endif
                            </span>
                            <span>{{ $alert['message'] }}</span>
                        </div>
                    @empty
                        <x-wfm.empty icon="check-circle" message="Sin alertas activas" class="h-24" />
                    @endforelse
                </div>
            </x-wfm.section>
        </div>

        {{-- AHT por Agente y Cola --}}
        <x-wfm.section title="AHT por Agente y Cola">
            <div class="h-72" wire:ignore>
                <x-apex-chart id="aht-queue-chart" :options="$ahtChartOptions" height="100%" />
            </div>
        </x-wfm.section>

        {{-- Roster Table --}}
        <x-wfm.section title="Roster del Equipo">
            <x-wfm.table :headers="['Agente', 'Estado', 'AHT', 'Llamadas', 'T. Hablado', 'Ocupación', 'Productividad']" compact>
                @forelse($roster as $row)
                    <flux:table.row>
                        <flux:table.cell class="font-medium text-xs">{{ $row['name'] }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="inline-flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full {{ $row['is_connected'] ? 'bg-wfm-success' : 'bg-wfm-danger' }}"></span>
                                <span class="font-mono text-xs">{{ $row['state'] }}</span>
                                @if($row['reason'])
                                    <span class="text-[9px] text-wfm-surface-muted">({{ $row['reason'] }})</span>
                                @endif
                            </span>
                        </flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $row['aht'] > 0 ? number_format($row['aht'], 1) . 's' : '—' }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $row['calls'] }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $row['talk_time'] > 0 ? gmdate('H:i:s', $row['talk_time']) : '—' }}</span></flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs">{{ $row['occupancy'] }}%</span></flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1.5">
                                <div class="h-1.5 w-12 bg-wfm-surface-border rounded-full overflow-hidden">
                                    <div class="h-full rounded-full {{ $row['productivity'] >= 80 ? 'bg-wfm-success' : ($row['productivity'] >= 60 ? 'bg-wfm-warning' : 'bg-wfm-danger') }}"
                                         style="width: {{ min(100, $row['productivity']) }}%"></div>
                                </div>
                                <span class="font-mono text-[10px] {{ $row['productivity'] >= 80 ? 'text-wfm-success' : ($row['productivity'] >= 60 ? 'text-wfm-warning' : 'text-wfm-danger') }}">{{ $row['productivity'] }}%</span>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center text-wfm-surface-muted text-xs py-8">
                            Sin agentes en este equipo
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-wfm.table>
        </x-wfm.section>
    @endif
</div>
