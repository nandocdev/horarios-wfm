<div wire:poll.{{ $refreshInterval }}s class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">{{ $greeting }}, {{ $displayName }}</h1>
                <p class="mt-2 text-sm text-slate-600">{{ $todayLabel }}</p>
                <p class="mt-1 text-sm font-medium text-slate-500">{{ $currentTime }}</p>
            </div>
            <div class="flex flex-col items-start gap-2 lg:items-end">
                <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    {{ $operationStatus['label'] }}
                </div>
                <div class="text-sm text-slate-500">Semana operativa {{ $weekRange }}</div>
            </div>
        </div>

            <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4 text-sm text-slate-600">
                <span class="rounded-full bg-slate-100 px-3 py-1 font-medium cursor-pointer hover:bg-slate-200" wire:click="$refresh">Actualizar</span>
                <span>Última sincronización {{ $footer['lastCalculation'] }}</span>
                <span class="text-slate-400">•</span>
                @if($shift['start'] !== '--:--')
                <span>Turno {{ $shift['start'] }} - {{ $shift['end'] }}</span>
                <span class="text-slate-400">•</span>
                @endif
                @if($shift['team'] && $shift['team'] !== '—')
                <span>Equipo {{ $shift['team'] }}</span>
                @endif
            </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($kpis as $kpi)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $kpi['label'] }}</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $kpi['value'] }}</p>
                @if (!empty($kpi['hint']))
                    <p class="mt-2 text-sm text-slate-500">{{ $kpi['hint'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Cobertura durante el día</h2>
                    <p class="mt-1 text-sm text-slate-500">Próximo riesgo {{ $nextRisk['time'] }} · Cobertura esperada {{ $nextRisk['coverage'] }}</p>
                </div>
                <div class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">Riesgo alto</div>
            </div>
            <div class="mt-6">
                @php
                    $maxVal = max(100, max(array_column($coverageSeries->toArray(), 'required')));
                    $svgW = 560; $svgH = 160; $padL = 35; $padR = 10; $padT = 10; $padB = 25;
                    $chartW = $svgW - $padL - $padR; $chartH = $svgH - $padT - $padB;
                    $count = count($coverageSeries); $step = $count > 1 ? $chartW / ($count - 1) : $chartW;
                    $points = [];
                    $points2 = [];
                    foreach ($coverageSeries as $i => $p) {
                        $x = $padL + ($i * $step);
                        $y1 = $padT + $chartH - (($p['required'] / $maxVal) * $chartH);
                        $y2 = $padT + $chartH - (($p['available'] / $maxVal) * $chartH);
                        $points[] = "{$x},{$y1}";
                        $points2[] = "{$x},{$y2}";
                    }
                    $areaReq = 'M'.$padL.','.($padT+$chartH).' L'.implode(' L', $points).' L'.($padL+(($count-1)*$step)).','.($padT+$chartH).' Z';
                    $areaAvail = 'M'.$padL.','.($padT+$chartH).' L'.implode(' L', $points2).' L'.($padL+(($count-1)*$step)).','.($padT+$chartH).' Z';
                    $lineReq = implode(' L', $points);
                    $lineAvail = implode(' L', $points2);
                @endphp
                <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" class="w-full h-auto max-h-48">
                    <!-- Grid lines -->
                    @foreach([80, 90, 100] as $pct)
                        @php($gy = $padT + $chartH - (($pct / $maxVal) * $chartH))
                        <line x1="{{ $padL }}" y1="{{ $gy }}" x2="{{ $svgW - $padR }}" y2="{{ $gy }}" stroke="#e2e8f0" stroke-width="1" />
                        <text x="{{ $padL - 5 }}" y="{{ $gy + 4 }}" text-anchor="end" class="text-[10px]" fill="#94a3b8">{{ $pct }}%</text>
                    @endforeach
                    <!-- Area (required) -->
                    <path d="{{ $areaReq }}" fill="#94a3b8" fill-opacity="0.15" />
                    <!-- Area (available) -->
                    <path d="{{ $areaAvail }}" fill="#3b82f6" fill-opacity="0.15" />
                    <!-- Line (required) -->
                    <path d="M{{ $lineReq }}" fill="none" stroke="#94a3b8" stroke-width="2" stroke-dasharray="4,3" />
                    <!-- Line (available) -->
                    <path d="M{{ $lineAvail }}" fill="none" stroke="#3b82f6" stroke-width="2" />
                    <!-- X labels -->
                    @foreach($coverageSeries as $i => $p)
                        <text x="{{ $padL + ($i * $step) }}" y="{{ $svgH - 5 }}" text-anchor="middle" class="text-[10px]" fill="#94a3b8">{{ $p['hour'] }}</text>
                    @endforeach
                </svg>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Distribución del personal</h2>
            @php
                $donutColors = ['#3b82f6', '#22c55e', '#f59e0b', '#94a3b8'];
                $total = array_sum(array_column($distribution, 'value'));
                $acc = 0;
                $segments = [];
                foreach ($distribution as $i => $d) {
                    $pct = $total > 0 ? ($d['value'] / $total) * 100 : 0;
                    $offset = $acc;
                    $len = $pct * 2.513; // circumference ~251.3
                    $segments[] = ['label' => $d['label'], 'value' => $d['value'], 'pct' => round($pct), 'color' => $donutColors[$i % count($donutColors)], 'offset' => $offset, 'len' => max(1, $len)];
                    $acc += $len;
                }
            @endphp
            <div class="mt-6 flex flex-col items-center gap-4 sm:flex-row">
                <svg viewBox="0 0 100 100" class="w-32 h-32 flex-shrink-0 -rotate-90">
                    @foreach($segments as $s)
                        <circle cx="50" cy="50" r="40" fill="none" stroke="{{ $s['color'] }}" stroke-width="12"
                            stroke-dasharray="{{ $s['len'] }} {{ 251.3 - $s['len'] }}"
                            stroke-dashoffset="{{ -$s['offset'] }}" />
                    @endforeach
                    <circle cx="50" cy="50" r="28" fill="white" />
                </svg>
                <div class="flex-1 space-y-3 w-full">
                    @foreach($segments as $s)
                        <div class="flex items-center gap-2 text-sm">
                            <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" style="background:{{ $s['color'] }}"></span>
                            <span class="flex-1 text-slate-600">{{ $s['label'] }}</span>
                            <span class="font-semibold text-slate-900">{{ $s['value'] }}</span>
                            <span class="text-slate-400 w-8 text-right">{{ $s['pct'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Colas</h2>
            <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Cola</th>
                            <th class="px-4 py-3 font-medium">Espera</th>
                            <th class="px-4 py-3 font-medium">Atendidas</th>
                            <th class="px-4 py-3 font-medium">AHT</th>
                            <th class="px-4 py-3 font-medium">SLA</th>
                            <th class="px-4 py-3 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($queues as $queue)
                            <tr class="@if ($queue['state'] === 'critical') bg-rose-50 @elseif ($queue['state'] === 'attention') bg-amber-50 @else bg-white @endif">
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $queue['name'] }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $queue['waiting'] }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $queue['handled'] }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $queue['aht'] }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $queue['sla'] }}</td>
                                <td class="px-4 py-3">
                                    @if ($queue['state'] === 'critical')
                                        <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">🔴</span>
                                    @elseif ($queue['state'] === 'attention')
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">🟠</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">🟢</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Incidencias Hoy</h2>
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                @foreach ($incidents as $incident)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">{{ $incident['label'] }}</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $incident['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Eventos próximos</h2>
            <div class="mt-6 space-y-4">
                @foreach ($events as $event)
                    <div class="flex gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="min-w-[3.5rem] rounded-lg bg-white px-2 py-2 text-center text-sm font-semibold text-slate-700">
                            {{ $event['time'] }}
                        </div>
                        <div>
                            <p class="font-medium text-slate-800">{{ $event['title'] }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $event['detail'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Solicitudes pendientes</h2>
            <div class="mt-6 space-y-3">
                @foreach ($requests as $request)
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                        <span class="text-sm font-medium text-slate-700">{{ $request['label'] }}</span>
                        <span class="rounded-full bg-white px-2.5 py-1 text-sm font-semibold text-slate-700">{{ $request['value'] }}</span>
                    </div>
                @endforeach
            </div>
            <button class="mt-4 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Ver Bandeja</button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Ranking Equipos</h2>
            <div class="mt-6 space-y-4">
                @foreach ($teams as $team)
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm text-slate-600">
                            <span>{{ $team['name'] }}</span>
                            <span class="font-semibold text-slate-900">{{ $team['value'] }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-blue-600" style="width: {{ $team['value'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Alertas Operativas</h2>
            <div class="mt-6 space-y-3">
                @foreach ($alerts as $alert)
                    @php
                        $tone = match ($alert['level']) {
                            'critical' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'dot' => 'bg-rose-500'],
                            'attention' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'dot' => 'bg-amber-500'],
                            default => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500'],
                        };
                    @endphp
                    <div class="flex items-start gap-3 rounded-xl border border-slate-200 {{ $tone['bg'] }} p-3">
                        <span class="mt-1 h-2.5 w-2.5 rounded-full {{ $tone['dot'] }}"></span>
                        <p class="text-sm font-medium {{ $tone['text'] }}">{{ $alert['message'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Tendencia semanal</h2>
            <div class="mt-6 space-y-5">
                @foreach ($trends as $trend)
                    @php
                        $sparkData = $trend['data'] ?? [];
                        $sparkCount = count($sparkData);
                        $sparkW = 160; $sparkH = 28; $sparkMax = max(1, max($sparkData));
                        $sparkPts = implode(' ', array_map(fn ($i, $v) => (($sparkCount > 1 ? ($i / ($sparkCount - 1)) : 0) * $sparkW).','.(($sparkMax - $v) / $sparkMax * $sparkH), array_keys($sparkData), $sparkData));
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 font-medium">{{ $trend['label'] }}</span>
                            @if(count($sparkData) > 1)
                                @php($last = end($sparkData); $prev = prev($sparkData); $diff = $last - $prev; $dir = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat'); @endphp
                                <span class="text-xs {{ $dir === 'up' ? 'text-emerald-600' : ($dir === 'down' ? 'text-rose-600' : 'text-slate-400') }}">
                                    @if($dir === 'up')↑ @elseif($dir === 'down')↓ @endif {{ number_format(abs($diff)) }}
                                </span>
                            @endif
                        </div>
                        @if($sparkCount > 1)
                            <svg viewBox="0 0 {{ $sparkW }} {{ $sparkH }}" class="w-full h-7 mt-1">
                                <polyline points="{{ $sparkPts }}" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Acciones rápidas</h2>
            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($quickActions as $action)
                    <button class="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">{{ $action['label'] }}</button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="text-sm text-slate-600">
                <span class="font-semibold text-slate-900">Usuarios conectados</span> {{ $footer['connectedUsers'] }}
            </div>
            <div class="text-sm text-slate-600">
                <span class="font-semibold text-slate-900">Último cálculo</span> {{ $footer['lastCalculation'] }}
            </div>
            <div class="text-sm text-slate-600">
                <span class="font-semibold text-slate-900">Última publicación de horarios</span> {{ $footer['lastSchedulesPublished'] }}
            </div>
            <div class="text-sm text-slate-600">
                <span class="font-semibold text-slate-900">Próxima actualización</span> {{ $footer['nextRefresh'] }}
            </div>
        </div>
    </div>
</div>
