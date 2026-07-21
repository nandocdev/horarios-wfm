<div class="space-y-6">
    @php
        $isHistorical = $employeeData['is_historical'] ?? false;
        $selectedDate = \Carbon\Carbon::parse($selectedDate);
        $isToday = $selectedDate->isToday();
    @endphp

    <x-wfm.page-header :title="$employeeData['name'] ?? 'Mi Jornada'" :description="$employeeData['team'] ?? ''">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <flux:button wire:click="previousDay" variant="ghost" icon="chevron-left" size="xs" />
                <flux:text class="font-mono text-sm {{ $isToday ? 'text-wfm-info font-semibold' : 'text-wfm-surface-muted' }}">
                    {{ $selectedDate->locale('es')->translatedFormat('l d F Y') }}
                    @if($isToday)
                        <span class="text-[10px] bg-wfm-info/10 text-wfm-info px-1.5 py-0.5 rounded font-mono">Hoy</span>
                    @endif
                </flux:text>
                <flux:button wire:click="nextDay" variant="ghost" icon="chevron-right" size="xs" @if($isToday) disabled @endif />
                @if(!$isHistorical)
                    <x-wfm.live-indicator :label="$employeeData['current_state'] ?? 'OFFLINE'" :color="($employeeData['is_connected'] ?? false) ? 'success' : 'danger'" />
                @endif
            </div>
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
            <x-wfm.kpi :value="$d['adherence'] ?? '--'" label="Adherencia" :trend="$adherenceVal > 0 ? $adherenceVal . '%' : ''" trend-direction="up" icon="check-badge" color="info" />
            <x-wfm.kpi :value="$d['occupancy'] . '%'" label="Ocupación" icon="cpu-chip" />
            <x-wfm.kpi :value="gmdate('H:i', $d['total_seconds'])" label="T. Conectado" icon="clock" />
            <x-wfm.kpi :value="gmdate('H:i', $d['productive_seconds'])" label="T. Productivo" icon="chart-bar" />
            <x-wfm.kpi :value="$d['total_calls']" label="Llamadas" :comparison="'SLA ' . $d['sla'] . '%'" icon="phone" />
            <x-wfm.kpi :value="($d['productivity_pct'] ?? 0) . '%" label="Productividad" icon="chart-pie" :comparison="$d['avg_handle_time'] !== null ? 'AHT ' . number_format($d['avg_handle_time'], 1) . 's' : ''" />
        </div>

        @if(!$isHistorical)
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <div class="lg:col-span-8">
                    <x-wfm.section title="Línea de Tiempo" :description="$selectedDate->locale('es')->translatedFormat('l d F Y')">
                        <div class="h-[28rem]">
                            @livewire('operations.agent-timeline', ['employeeId' => $targetEmployee->id], key('timeline-'.$targetEmployee->id))
                        </div>
                    </x-wfm.section>
                </div>

                <div class="lg:col-span-4 space-y-4">
                    <x-wfm.section title="Transiciones de Estado">
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
                                    <span class="font-mono text-wfm-surface-muted w-10">{{ \Carbon\Carbon::parse($t['transition_time'])->format('H:i') }}</span>
                                    <span class="font-medium flex-1">{{ strtoupper($t['agent_state'] ?? '') }}</span>
                                    <span class="font-mono text-wfm-surface-muted">{{ gmdate('i:s', $t['duration'] ?? 0) }}</span>
                                </div>
                            @empty
                                <x-wfm.empty icon="clock" message="Sin transiciones registradas" />
                            @endforelse
                        </div>
                    </x-wfm.section>
                </div>
            </div>
        @endif

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

            @if(!$isHistorical)
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
            @else
                <x-wfm.section title="Resumen del Día">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-wfm-surface rounded-md">
                            <flux:icon.clock class="w-5 h-5 text-wfm-surface-muted" />
                            <div>
                                <p class="text-sm font-bold text-wfm-navy-800 dark:text-white">Reporte Histórico</p>
                                <p class="text-xs text-wfm-surface-muted">{{ $selectedDate->locale('es')->translatedFormat('l d F Y') }}</p>
                            </div>
                        </div>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                <span class="text-wfm-navy-700">Productividad</span>
                                <span class="font-mono text-wfm-navy-700">{{ $d['productivity_pct'] ?? '--' }}%</span>
                            </div>
                            <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                <span class="text-wfm-navy-700">Tiempo Prom. Atención</span>
                                <span class="font-mono text-wfm-navy-700">{{ $d['avg_handle_time'] !== null ? number_format($d['avg_handle_time'], 1) . 's' : '--' }}</span>
                            </div>
                            <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                <span class="text-wfm-navy-700">Llamadas Atendidas</span>
                                <span class="font-mono text-wfm-navy-700">{{ $d['handled_calls'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </x-wfm.section>
            @endif
        </div>
    @endif
</div>
