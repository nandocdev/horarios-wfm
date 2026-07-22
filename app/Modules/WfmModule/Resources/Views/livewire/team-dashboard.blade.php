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

        {{-- AHT Distribution by Agent --}}
        @php
            $rosterCol = collect($roster);
            $rosterWithAht = $rosterCol->filter(fn($r) => $r['aht'] > 0)->values();
            $ahtValues = $rosterWithAht->pluck('aht')->sort()->values();
            $ahtCount = $ahtValues->count();
            $ahtMin = $ahtValues->first() ?? 0;
            $ahtMax = $ahtValues->last() ?? 0;
            $ahtMedian = $ahtCount > 0 ? $ahtValues[floor($ahtCount / 2)] : 0;
            $ahtQ1 = $ahtCount > 0 ? $ahtValues[floor($ahtCount / 4)] : 0;
            $ahtQ3 = $ahtCount > 0 ? $ahtValues[floor(($ahtCount * 3) / 4)] : 0;
            $ahtIqr = $ahtQ3 - $ahtQ1;
            $ahtLowerWhisker = max($ahtMin, $ahtQ1 - 1.5 * $ahtIqr);
            $ahtUpperWhisker = min($ahtMax, $ahtQ3 + 1.5 * $ahtIqr);
            $outliers = $rosterWithAht->filter(fn($r) => $r['aht'] < $ahtLowerWhisker || $r['aht'] > $ahtUpperWhisker);
            $boxMax = $ahtUpperWhisker ?: 1;
        @endphp

        @if($ahtCount > 0)
            <x-wfm.section title="Distribución AHT por Agente">
                <div class="relative h-40">
                    <svg viewBox="0 0 400 100" class="w-full h-full">
                        {{-- Eje Y --}}
                        <text x="5" y="12" font-size="6" fill="#9ca3af">AHT (s)</text>
                        <text x="5" y="25" font-size="5" fill="#d1d5db">{{ number_format($ahtMax, 0) }}</text>
                        <text x="5" y="50" font-size="5" fill="#d1d5db">{{ number_format(($ahtMax + $ahtMin) / 2, 0) }}</text>
                        <text x="5" y="75" font-size="5" fill="#d1d5db">{{ number_format($ahtMin, 0) }}</text>

                        {{-- Box plot --}}
                        @php
                            $scale = 60 / max($boxMax, 1);
                            $cx = 200;
                            $q1y = 70 - ($ahtQ1 * $scale);
                            $q3y = 70 - ($ahtQ3 * $scale);
                            $medY = 70 - ($ahtMedian * $scale);
                            $lowY = 70 - ($ahtLowerWhisker * $scale);
                            $highY = 70 - ($ahtUpperWhisker * $scale);
                            $boxH = max(2, $q1y - $q3y);
                        @endphp

                        {{-- Whiskers --}}
                        <line x1="{{ $cx }}" y1="{{ $lowY }}" x2="{{ $cx }}" y2="{{ $q3y }}" stroke="#93c5fd" stroke-width="1.5" />
                        <line x1="{{ $cx }}" y1="{{ $q1y }}" x2="{{ $cx }}" y2="{{ $highY }}" stroke="#93c5fd" stroke-width="1.5" />
                        <line x1="{{ $cx - 15 }}" y1="{{ $lowY }}" x2="{{ $cx + 15 }}" y2="{{ $lowY }}" stroke="#93c5fd" stroke-width="1.5" />
                        <line x1="{{ $cx - 15 }}" y1="{{ $highY }}" x2="{{ $cx + 15 }}" y2="{{ $highY }}" stroke="#93c5fd" stroke-width="1.5" />

                        {{-- Box (Q1 to Q3) --}}
                        <rect x="{{ $cx - 20 }}" y="{{ $q3y }}" width="40" height="{{ $boxH }}" fill="rgba(59,130,246,0.2)" stroke="#3b82f6" stroke-width="1.5" rx="2" />

                        {{-- Median line --}}
                        <line x1="{{ $cx - 20 }}" y1="{{ $medY }}" x2="{{ $cx + 20 }}" y2="{{ $medY }}" stroke="#ef4444" stroke-width="2" />

                        {{-- Mean marker --}}
                        @php $meanY = 70 - (($ahtValues->avg() ?? 0) * $scale); @endphp
                        <circle cx="{{ $cx + 28 }}" cy="{{ $meanY }}" r="3" fill="#10b981" stroke="white" stroke-width="1" />

                        {{-- Outliers --}}
                        @foreach($outliers as $o)
                            @php $oy = 70 - ($o['aht'] * $scale); @endphp
                            <circle cx="{{ $cx + 35 }}" cy="{{ $oy }}" r="2.5" fill="none" stroke="#ef4444" stroke-width="1" />
                            <text x="{{ $cx + 40 }}" y="{{ $oy + 2 }}" font-size="4" fill="#ef4444">{{ $o['name'] }}</text>
                        @endforeach

                        {{-- Legend --}}
                        <rect x="10" y="82" width="6" height="6" fill="rgba(59,130,246,0.2)" stroke="#3b82f6" stroke-width="0.5" rx="1" />
                        <text x="20" y="88" font-size="5" fill="#9ca3af">IQR ({{ $ahtQ1 }}-{{ $ahtQ3 }})</text>
                        <line x1="80" y1="85" x2="92" y2="85" stroke="#ef4444" stroke-width="2" />
                        <text x="96" y="88" font-size="5" fill="#9ca3af">Mediana {{ number_format($ahtMedian, 0) }}s</text>
                        <circle cx="150" cy="85" r="3" fill="#10b981" stroke="white" stroke-width="0.5" />
                        <text x="158" y="88" font-size="5" fill="#9ca3af">Media {{ number_format($ahtValues->avg(), 0) }}s</text>
                    </svg>
                </div>
            </x-wfm.section>
        @endif

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
