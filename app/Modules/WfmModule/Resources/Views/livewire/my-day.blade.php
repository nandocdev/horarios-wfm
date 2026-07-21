<div class="space-y-4">
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
                    @if($employeeData['disconnected_with_activity'] ?? false)
                        <x-wfm.live-indicator label="Desconexión Abrupta" color="warning" :pulse="true" />
                    @else
                        <x-wfm.live-indicator :label="$employeeData['current_state'] ?? 'OFFLINE'" :color="($employeeData['is_connected'] ?? false) ? 'success' : 'danger'" />
                    @endif
                @endif
            </div>
        </x-slot:actions>
    </x-wfm.page-header>

    @if(!$employeeData)
        <x-wfm.empty icon="user" message="Seleccione un empleado para ver su jornada" />
    @else
        @php $d = $employeeData; @endphp

        {{-- Hero Metrics — single compact row --}}
        <div class="card-wfm p-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <div class="text-center">
                    <div class="text-2xl font-bold text-wfm-navy-800 dark:text-white leading-none">{{ gmdate('H:i', $d['total_seconds']) }}</div>
                    <div class="text-[10px] font-medium text-wfm-surface-muted uppercase tracking-wider mt-1.5">Conectado</div>
                    @if($d['scheduled_entry'] !== '--:--')
                        <div class="text-[10px] text-wfm-surface-muted mt-0.5">Turno {{ $d['scheduled_entry'] }} - {{ $d['scheduled_end'] }}</div>
                    @endif
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-wfm-success leading-none">{{ gmdate('H:i', $d['productive_seconds']) }}</div>
                    <div class="text-[10px] font-medium text-wfm-surface-muted uppercase tracking-wider mt-1.5">Productivo</div>
                </div>
                <div class="text-center">
                    @php $adherenceVal = is_numeric($d['adherence']) ? (float) $d['adherence'] : 0; @endphp
                    <div class="text-2xl font-bold {{ $adherenceVal >= 90 ? 'text-wfm-success' : ($adherenceVal >= 80 ? 'text-wfm-warning' : 'text-wfm-danger') }} leading-none">{{ $d['adherence'] ?? '--' }}%</div>
                    <div class="text-[10px] font-medium text-wfm-surface-muted uppercase tracking-wider mt-1.5">Adherencia</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-wfm-info leading-none">{{ $d['occupancy'] }}%</div>
                    <div class="text-[10px] font-medium text-wfm-surface-muted uppercase tracking-wider mt-1.5">Ocupación</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-wfm-navy-800 dark:text-white leading-none">{{ $d['total_calls'] }}</div>
                    <div class="text-[10px] font-medium text-wfm-surface-muted uppercase tracking-wider mt-1.5">Llamadas</div>
                    <div class="text-[10px] text-wfm-surface-muted mt-0.5">SLA {{ $d['sla'] }}%</div>
                </div>
            </div>
        </div>

        {{-- Timeline Gantt + Estado Actual --}}
        @if(!$isHistorical)
            @php
                $segments = [
                    ['key' => 'TALKING', 'label' => 'TALKING', 'color' => 'bg-wfm-info', 'seconds' => $d['talk']],
                    ['key' => 'READY', 'label' => 'READY', 'color' => 'bg-wfm-success', 'seconds' => $d['ready']],
                    ['key' => 'WORK', 'label' => 'ACW', 'color' => 'bg-purple-500', 'seconds' => $d['acw']],
                    ['key' => 'RESERVED', 'label' => 'RESERVED', 'color' => 'bg-cyan-500', 'seconds' => $d['reserved']],
                    ['key' => 'NOT_READY', 'label' => 'NO DISP.', 'color' => 'bg-wfm-warning', 'seconds' => $d['not_ready']],
                    ['key' => 'LUNCH', 'label' => 'ALMUERZO', 'color' => 'bg-orange-400', 'seconds' => $d['lunch']],
                    ['key' => 'BREAK', 'label' => 'DESCANSO', 'color' => 'bg-yellow-400', 'seconds' => $d['break']],
                    ['key' => 'OFFLINE', 'label' => 'OFFLINE', 'color' => 'bg-wfm-danger', 'seconds' => $d['offline']],
                ];
                $activeSegments = array_filter($segments, fn($s) => $s['seconds'] > 0);
                $totalForBar = array_sum(array_column($activeSegments, 'seconds')) ?: 1;
            @endphp

            <div class="card-wfm p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-wfm-surface-muted uppercase tracking-wider">Línea de Tiempo</h3>
                    <flux:modal.trigger name="transitions-drawer">
                        <flux:button variant="ghost" size="xs" icon="bars-3">Ver detalle</flux:button>
                    </flux:modal.trigger>
                </div>

                {{-- Gantt bar --}}
                <div class="h-6 rounded-full overflow-hidden flex bg-wfm-surface">
                    @foreach($activeSegments as $seg)
                        <div class="{{ $seg['color'] }} h-full" style="width: {{ round(($seg['seconds'] / $totalForBar) * 100) }}%" title="{{ $seg['label'] }}: {{ gmdate('H:i:s', $seg['seconds']) }}"></div>
                    @endforeach
                </div>

                {{-- Compact legend --}}
                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
                    @foreach($activeSegments as $seg)
                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-wfm-surface-muted">
                            <span class="w-2 h-2 rounded-sm {{ $seg['color'] }}"></span>
                            {{ $seg['label'] }}
                            <span class="font-mono">{{ gmdate('H:i:s', $seg['seconds']) }}</span>
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- Transitions drawer --}}
            <flux:modal name="transitions-drawer" class="md:max-w-xl">
                <div class="space-y-4">
                    <div>
                        <flux:heading size="lg">Transiciones de Estado</flux:heading>
                        <flux:subheading>{{ $selectedDate->locale('es')->translatedFormat('l d F Y') }}</flux:subheading>
                    </div>

                    <div class="space-y-0.5 max-h-[32rem] overflow-y-auto">
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

                    <div class="flex justify-end">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cerrar</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        @endif

        {{-- Cumplimiento + Estado Actual --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <x-wfm.section title="Cumplimiento del Horario">
                    @php
                        $entryDiff = $d['entry_diff'];
                        $entryLabel = $entryDiff !== null ? ($entryDiff <= 0 ? (string) $entryDiff : '+' . $entryDiff) . ' min' : '—';
                        $lunchReal = $d['lunch'] > 0 ? gmdate('H:i', $d['lunch']) : '—';
                        $breakReal = $d['break'] > 0 ? gmdate('H:i', $d['break']) : '—';
                    @endphp
                    <div class="space-y-1">
                        @foreach([
                            ['label' => 'Entrada', 'sched' => $d['scheduled_entry'], 'real' => $d['real_entry'] ?? '--:--', 'diff' => $entryLabel, 'ok' => ($entryDiff !== null && $entryDiff <= 5)],
                            ['label' => 'Almuerzo', 'sched' => $d['lunch_start'] ?? '--:--', 'real' => $lunchReal, 'diff' => '—', 'ok' => true],
                            ['label' => 'Regreso Alm.', 'sched' => $d['lunch_end'] ?? '--:--', 'real' => '—', 'diff' => '—', 'ok' => true],
                            ['label' => 'Descanso', 'sched' => $d['break_start'] ?? '--:--', 'real' => $breakReal, 'diff' => '—', 'ok' => true],
                        ] as $c)
                            <div class="flex items-center justify-between py-1.5 px-2 {{ !$loop->last ? 'border-b border-wfm-surface-border/30' : '' }}">
                                <span class="text-xs font-medium text-wfm-navy-700 w-20">{{ $c['label'] }}</span>
                                <span class="text-xs font-mono text-wfm-surface-muted w-16 text-center">{{ $c['sched'] }}</span>
                                <span class="text-xs font-mono text-wfm-surface-muted w-16 text-center">{{ $c['real'] }}</span>
                                <span class="text-xs font-mono w-16 text-center {{ $c['ok'] ? 'text-wfm-success' : 'text-wfm-danger' }}">{{ $c['diff'] }}</span>
                                <span class="w-20 text-right">
                                    <x-wfm.agent-status :status="$c['ok'] ? 'available' : 'busy'" :label="$c['ok'] ? 'A tiempo' : 'Retraso'" size="xs" />
                                </span>
                            </div>
                        @endforeach
                    </div>
                    @if($d['has_exceptions'])
                        <div class="mt-2 flex items-center gap-2 text-xs text-wfm-warning">
                            <flux:icon.exclamation-triangle class="w-3.5 h-3.5" />
                            <span>Con excepción de horario</span>
                        </div>
                    @endif
                </x-wfm.section>
            </div>

            @if(!$isHistorical)
                <x-wfm.section title="Estado Actual">
                    <div class="space-y-2">
                        @if($d['disconnected_with_activity'] ?? false)
                            <div class="flex items-center gap-2 p-2 bg-wfm-warning/10 rounded-md text-xs">
                                <span class="live-pulse">
                                    <span class="live-pulse-dot bg-wfm-warning"></span>
                                    <span class="live-pulse-ring bg-wfm-warning"></span>
                                </span>
                                <span class="text-wfm-warning font-medium">Sesión Perdida</span>
                            </div>
                        @endif
                        <div class="flex items-center gap-2 p-2 bg-wfm-surface rounded-md">
                            <span class="w-2.5 h-2.5 rounded-full {{ $d['is_connected'] ? 'bg-wfm-success' : 'bg-wfm-danger' }}"></span>
                            <span class="text-sm font-bold text-wfm-navy-800 dark:text-white">{{ $d['current_state'] }}</span>
                            @if($d['reason'])
                                <span class="text-[10px] text-wfm-surface-muted ml-auto">{{ $d['reason'] }}</span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-1 text-[10px]">
                            @foreach([['TALKING', $d['talk']], ['READY', $d['ready']], ['ALMUERZO', $d['lunch']], ['DESCANSO', $d['break']]] as [$label, $seconds])
                                <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                                    <span class="text-wfm-navy-700 font-medium">{{ $label }}</span>
                                    <span class="font-mono text-wfm-navy-700">{{ gmdate('H:i:s', $seconds) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-wfm.section>
            @else
                <x-wfm.section title="Resumen del Día">
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                            <span class="text-wfm-navy-700 font-medium">Productividad</span>
                            <span class="font-mono">{{ $d['productivity_pct'] ?? '--' }}%</span>
                        </div>
                        <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                            <span class="text-wfm-navy-700 font-medium">T. Prom. Atención</span>
                            <span class="font-mono">{{ $d['avg_handle_time'] !== null ? number_format($d['avg_handle_time'], 1) . 's' : '--' }}</span>
                        </div>
                        <div class="flex justify-between p-1.5 bg-wfm-surface rounded">
                            <span class="text-wfm-navy-700 font-medium">Llamadas Atendidas</span>
                            <span class="font-mono">{{ $d['handled_calls'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-wfm-surface-border/30 text-[10px] text-wfm-surface-muted">
                        Reporte del {{ $selectedDate->locale('es')->translatedFormat('l d F Y') }}
                    </div>
                </x-wfm.section>
            @endif
        </div>
    @endif
</div>
