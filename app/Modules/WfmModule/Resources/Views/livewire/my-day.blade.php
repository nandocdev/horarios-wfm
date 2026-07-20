<div class="space-y-6">
    <x-wfm.page-header :title="$employeeData['name'] ?? 'Mi Jornada'" :description="$employeeData['team'] ?? ''">
        <x-slot:actions>
            <x-wfm.live-indicator :label="$employeeData['current_state'] ?? 'OFFLINE'" :color="($employeeData['is_connected'] ?? false) ? 'success' : 'danger'" />
            <flux:text size="sm" class="text-wfm-surface-muted">{{ now()->locale('es')->translatedFormat('l d F Y') }}</flux:text>
        </x-slot:actions>
    </x-wfm.page-header>

    @if(!$employeeData)
        <x-wfm.empty icon="user" message="Seleccione un empleado para ver su jornada" />
    @else
        @php $d = $employeeData; @endphp

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <x-wfm.kpi :value="$d['scheduled_entry'] . ' - ' . $d['scheduled_end']" label="Turno" icon="clock" />
            <x-wfm.kpi :value="$d['real_entry'] ?? '--:--'" label="Entrada Real" :comparison="$d['entry_diff'] !== null ? ($d['entry_diff'] <= 0 ? (string) $d['entry_diff'] . ' min' : '+' . $d['entry_diff'] . ' min') : ''" :color="$d['entry_diff'] !== null && $d['entry_diff'] > 0 ? 'warning' : 'success'" />
            <x-wfm.kpi :value="gmdate('H:i:s', $d['total_seconds'])" label="Tiempo Conectado" icon="signal" />
            <x-wfm.kpi :value="gmdate('H:i:s', $d['productive_seconds'])" label="T. Productivo" icon="chart-bar" />
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
            @php
                $adherenceVal = is_numeric($d['adherence']) ? (float) $d['adherence'] : 0;
            @endphp
            <x-wfm.kpi :value="$d['adherence']" label="Adherencia" :trend="$adherenceVal . '%'" trend-direction="up" icon="check-badge" color="info" />
            <x-wfm.kpi :value="$d['occupancy'] . '%'" label="Ocupación" icon="cpu-chip" />
            <x-wfm.kpi :value="gmdate('H:i', $d['total_seconds'])" label="T. Conectado" icon="clock" />
            <x-wfm.kpi :value="gmdate('H:i', $d['productive_seconds'])" label="T. Productivo" icon="chart-bar" />
            <x-wfm.kpi :value="$d['total_calls']" label="Llamadas" :comparison="'SLA ' . $d['sla'] . '%'" icon="phone" />
            <x-wfm.kpi :value="round(($d['total_seconds'] - $d['productive_seconds']) / max($d['total_seconds'], 1) * 100, 1) . '%'" label="Shrinkage" icon="chart-pie" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <div class="lg:col-span-8">
                <x-wfm.section title="Línea de Tiempo" description="Hoy">
                    <div class="h-[28rem]">
                        @livewire('operations.agent-timeline', ['employeeId' => $targetEmployee->id], key('timeline-'.$targetEmployee->id))
                    </div>
                </x-wfm.section>
            </div>

            <div class="lg:col-span-4 space-y-4">
                <x-wfm.section title="Eventos Recientes">
                    <div class="h-[28rem] overflow-y-auto">
                        @forelse($d['transitions'] as $t)
                            <div class="flex items-center gap-2 py-1 text-xs">
                                @php
                                    $stateColor = match(strtoupper($t['agent_state'] ?? '')) {
                                        'READY' => 'bg-wfm-success',
                                        'TALKING' => 'bg-wfm-info',
                                        'WORK', 'ACW' => 'bg-purple-500',
                                        'NOT_READY' => 'bg-wfm-warning',
                                        'LOGOUT', 'OFFLINE' => 'bg-wfm-danger',
                                        default => 'bg-wfm-surface-muted',
                                    };
                                @endphp
                                <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $stateColor }}"></span>
                                <span class="font-mono text-wfm-surface-muted w-10">{{ Carbon\Carbon::parse($t['transition_time'])->format('H:i') }}</span>
                                <span class="font-medium flex-1">{{ strtoupper($t['agent_state'] ?? '') }}</span>
                                <span class="font-mono text-wfm-surface-muted">{{ gmdate('i:s', $t['duration'] ?? 0) }}</span>
                            </div>
                        @empty
                            <x-wfm.empty icon="clock" message="Sin eventos para hoy" />
                        @endforelse
                    </div>
                </x-wfm.section>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <x-wfm.section title="Cumplimiento del Horario">
                    <x-wfm.table :headers="['Evento', 'Programado', 'Real', 'Diferencia', 'Estado']" compact>
                        @php
                            $entryDiff = $d['entry_diff'];
                            $entryLabel = $entryDiff !== null ? ($entryDiff <= 0 ? (string) $entryDiff : '+' . $entryDiff) . ' min' : '—';
                            $lunchReal = $d['lunch'] > 0 ? gmdate('H:i', $d['lunch']) : '—';
                            $breakReal = $d['break'] > 0 ? gmdate('H:i', $d['break']) : '—';
                            $compliance = [
                                ['label' => 'Entrada', 'sched' => $d['scheduled_entry'], 'real' => $d['real_entry'] ?? '--:--', 'diff' => $entryLabel, 'ok' => ($entryDiff !== null && $entryDiff <= 5)],
                                ['label' => 'Almuerzo', 'sched' => $d['lunch_start'] ?? '--:--', 'real' => $lunchReal, 'diff' => '—', 'ok' => true],
                                ['label' => 'Regreso Almuerzo', 'sched' => $d['lunch_end'] ?? '--:--', 'real' => '—', 'diff' => '—', 'ok' => true],
                                ['label' => 'Descanso', 'sched' => $d['break_start'] ?? '--:--', 'real' => $breakReal, 'diff' => '—', 'ok' => true],
                            ];
                        @endphp
                        @foreach($compliance as $c)
                            <flux:table.row>
                                <flux:table.cell class="font-medium">{{ $c['label'] }}</flux:table.cell>
                                <flux:table.cell><span class="font-mono">{{ $c['sched'] }}</span></flux:table.cell>
                                <flux:table.cell><span class="font-mono">{{ $c['real'] }}</span></flux:table.cell>
                                <flux:table.cell>
                                    <span class="font-mono {{ $c['ok'] ? 'text-wfm-success' : 'text-wfm-danger' }}">{{ $c['diff'] }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <x-wfm.agent-status :status="$c['ok'] ? 'available' : 'busy'" :label="$c['ok'] ? 'A tiempo' : 'Retraso'" size="xs" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </x-wfm.table>
                    @if($d['has_exceptions'])
                        <x-wfm.adherence-badge :value="0" target="1" size="xs" />
                        <span class="text-xs text-wfm-warning ml-1">Con excepción de horario</span>
                    @endif
                </x-wfm.section>
            </div>

            <x-wfm.section title="Estado Actual">
                <div class="space-y-3">
                    <div class="flex items-center gap-3 p-3 bg-wfm-surface rounded-md">
                        <span class="w-3 h-3 rounded-full {{ $d['is_connected'] ? 'bg-wfm-success' : 'bg-wfm-danger' }}"></span>
                        <div>
                            <p class="text-sm font-bold text-wfm-navy-800 dark:text-white">{{ $d['current_state'] }}</p>
                            @if($d['reason'])
                                <p class="text-xs text-wfm-surface-muted">{{ $d['reason'] }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-1 text-xs">
                        @foreach([['TALKING', $d['talk']], ['READY', $d['ready']], ['ALMUERZO', $d['lunch']], ['DESCANSO', $d['break']]] as [$label, $seconds])
                            <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                <span class="text-wfm-navy-700">{{ $label }}</span>
                                <span class="font-mono text-wfm-navy-700">{{ gmdate('H:i:s', $seconds) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-wfm.section>
        </div>
    @endif
</div>
