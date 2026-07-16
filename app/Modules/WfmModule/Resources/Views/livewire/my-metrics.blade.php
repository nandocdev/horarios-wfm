<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white dark:bg-zinc-900 rounded-xl p-4 border border-zinc-200 dark:border-zinc-800">
        <div class="flex items-center gap-3">
            <flux:avatar size="md" />
            <div>
                <flux:heading size="lg">{{ $employee?->full_name ?? 'Sin empleado' }}</flux:heading>
                <flux:text size="sm" class="text-slate-500">{{ $employee?->team?->name ?? '—' }}</flux:text>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right">
                <flux:text size="sm" class="font-medium">{{ $currentDate->locale('es')->translatedFormat('l d F Y') }}</flux:text>
            </div>
            <div class="flex gap-1">
                <flux:button wire:click="previousDay" icon="chevron-left" size="xs" variant="subtle" />
                <flux:button wire:click="$set('selectedDate', '{{ now()->toDateString() }}')" size="xs" variant="subtle">Hoy</flux:button>
                <flux:button wire:click="nextDay" icon="chevron-right" size="xs" variant="subtle" />
            </div>
            <flux:badge color="{{ $states['is_connected'] ?? false ? 'green' : 'red' }}" size="sm" inset="top" class="font-bold">
                {{ ($states['current'] ?? 'OFFLINE') === 'READY' ? 'Conectado Ready' : ($states['current'] ?? 'OFFLINE') }}
            </flux:badge>
        </div>
    </div>

    @if(!$employee)
        <flux:card><div class="text-center py-12 text-slate-400">Sin perfil de empleado asociado</div></flux:card>
    @else
        {{-- Summary Bar --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">Turno</flux:text>
                <flux:heading size="lg" class="mt-1">{{ $states['scheduled_entry'] }} - {{ $states['scheduled_end'] }}</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">Entrada</flux:text>
                <flux:heading size="lg" class="mt-1">{{ $states['real_entry'] ?? '--:--' }}</flux:heading>
                @if($states['entry_diff'] !== null)
                    <flux:text size="xs" class="{{ (int) $states['entry_diff'] <= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $states['entry_diff'] }} min
                    </flux:text>
                @endif
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">Tiempo Conectado</flux:text>
                <flux:heading size="lg" class="mt-1 font-mono">{{ gmdate('H:i:s', $states['total_seconds']) }}</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">T. Productivo</flux:text>
                <flux:heading size="lg" class="mt-1 font-mono">{{ gmdate('H:i:s', $states['productive_seconds']) }}</flux:heading>
                <flux:text size="xs" class="text-green-600">{{ $states['productivity_pct'] }}%</flux:text>
            </flux:card>
        </div>

        {{-- Hero KPIs --}}
        @if(!empty($heroKpis))
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach(['adherence', 'occupancy', 'coverage', 'service_level'] as $key)
                    @isset($heroKpis[$key])
                        @php $kpi = $heroKpis[$key]; @endphp
                        <flux:card class="text-center">
                            <flux:text size="xs" class="text-slate-500 uppercase font-bold">{{ $kpi['label'] }}</flux:text>
                            <flux:heading size="3xl" class="mt-1 font-bold">{{ $kpi['value'] }}</flux:heading>
                            <flux:badge size="sm" color="{{ $kpi['status'] === 'success' ? 'green' : ($kpi['status'] === 'warning' ? 'amber' : 'red') }}" inset="top" class="mt-1">
                                {{ $kpi['status'] === 'success' ? '🟢' : ($kpi['status'] === 'warning' ? '🟡' : '🔴') }}
                            </flux:badge>
                        </flux:card>
                    @endisset
                @endforeach
            </div>
        @endif

        {{-- Timeline placeholder --}}
        <flux:card>
            <flux:heading size="sm" class="mb-2">Mi Jornada</flux:heading>
            <div class="h-8 bg-slate-100 dark:bg-slate-800 rounded-full relative overflow-hidden">
                @php
                    $dayStart = 6; $dayEnd = 18; $totalSlots = ($dayEnd - $dayStart) * 2;
                    $segments = [
                        ['pct' => 20, 'color' => 'bg-green-400'],
                        ['pct' => 15, 'color' => 'bg-blue-500'],
                        ['pct' => 5, 'color' => 'bg-purple-400'],
                        ['pct' => 10, 'color' => 'bg-green-400'],
                        ['pct' => 8, 'color' => 'bg-yellow-300'],
                        ['pct' => 12, 'color' => 'bg-green-400'],
                        ['pct' => 10, 'color' => 'bg-blue-500'],
                        ['pct' => 10, 'color' => 'bg-orange-400'],
                        ['pct' => 5, 'color' => 'bg-blue-500'],
                        ['pct' => 5, 'color' => 'bg-green-400'],
                    ];
                @endphp
                <div class="flex h-full">
                    @foreach($segments as $seg)
                        <div class="{{ $seg['color'] }}" style="width: {{ $seg['pct'] }}%"></div>
                    @endforeach
                </div>
            </div>
            <div class="flex justify-between text-xs text-slate-400 mt-1">
                <span>06:00</span><span>08:00</span><span>10:00</span><span>12:00</span><span>14:00</span><span>16:00</span><span>18:00</span>
            </div>
            <div class="flex gap-4 mt-2 text-xs text-slate-500">
                <span><span class="inline-block w-3 h-3 rounded-full bg-green-400 align-middle mr-1"></span>Ready</span>
                <span><span class="inline-block w-3 h-3 rounded-full bg-blue-500 align-middle mr-1"></span>Talking</span>
                <span><span class="inline-block w-3 h-3 rounded-full bg-purple-400 align-middle mr-1"></span>ACW</span>
                <span><span class="inline-block w-3 h-3 rounded-full bg-yellow-300 align-middle mr-1"></span>Break</span>
                <span><span class="inline-block w-3 h-3 rounded-full bg-orange-400 align-middle mr-1"></span>Lunch</span>
                <span><span class="inline-block w-3 h-3 rounded-full bg-red-400 align-middle mr-1"></span>Offline</span>
            </div>
        </flux:card>

        {{-- Cumplimiento + Productividad --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Cumplimiento --}}
            <flux:card>
                <flux:heading size="sm" class="mb-3">Cumplimiento del Horario</flux:heading>
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs text-slate-500 uppercase border-b dark:border-zinc-800">
                        <th class="pb-2 font-semibold">Evento</th>
                        <th class="pb-2 font-semibold text-center">Programado</th>
                        <th class="pb-2 font-semibold text-center">Real</th>
                        <th class="pb-2 font-semibold text-right">Dif</th>
                        <th class="pb-2 font-semibold text-center">Estado</th>
                    </tr></thead>
                    <tbody>
                        @php
                            $entryDiff = $states['entry_diff'];
                            $entryDiffLabel = $entryDiff !== null ? ($entryDiff <= 0 ? (string) $entryDiff : '+'.$entryDiff) : '—';
                            $events = [
                                ['label' => 'Entrada', 'sched' => $states['scheduled_entry'], 'real' => $states['real_entry'] ?? '--:--', 'diff' => $entryDiffLabel, 'ok' => ($entryDiff !== null && $entryDiff <= 5)],
                                ['label' => 'Almuerzo', 'sched' => $states['lunch_start'] ?? '--:--', 'real' => '—', 'diff' => '—', 'ok' => true],
                                ['label' => 'Regreso', 'sched' => $states['lunch_end'] ?? '--:--', 'real' => '—', 'diff' => '—', 'ok' => true],
                                ['label' => 'Descanso', 'sched' => $states['break_start'] ?? '--:--', 'real' => '—', 'diff' => '—', 'ok' => true],
                            ];
                        @endphp
                        @foreach($events as $e)
                            <tr class="border-t dark:border-zinc-800">
                                <td class="py-2 font-medium">{{ $e['label'] }}</td>
                                <td class="py-2 text-center font-mono">{{ $e['sched'] }}</td>
                                <td class="py-2 text-center font-mono">{{ $e['real'] }}</td>
                                <td class="py-2 text-right font-mono {{ $e['ok'] ? 'text-green-600' : 'text-red-600' }}">{{ $e['diff'] }}</td>
                                <td class="py-2 text-center">{{ $e['ok'] ? '🟢' : '🔴' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </flux:card>

            {{-- Productividad --}}
            <flux:card>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:heading size="sm" class="mb-2 text-green-600">Tiempo Productivo</flux:heading>
                        <div class="space-y-1 text-sm">
                            @foreach([
                                ['label' => 'Talking', 'sec' => $states['talk']],
                                ['label' => 'Ready', 'sec' => $states['ready']],
                                ['label' => 'ACW', 'sec' => $states['acw']],
                                ['label' => 'Reserved', 'sec' => $states['reserved']],
                            ] as $item)
                                <div class="flex justify-between p-1.5 bg-slate-50 dark:bg-zinc-900 rounded">
                                    <span>{{ $item['label'] }}</span>
                                    <span class="font-mono">{{ gmdate('H:i:s', $item['sec']) }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between pt-2 border-t dark:border-zinc-800 font-bold">
                                <span>Total</span>
                                <span class="font-mono">{{ gmdate('H:i:s', $states['productive_seconds']) }}</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <flux:heading size="sm" class="mb-2 text-red-500">T. No Productivo</flux:heading>
                        <div class="space-y-1 text-sm">
                            @foreach([
                                ['label' => 'Almuerzo', 'sec' => $states['lunch']],
                                ['label' => 'Descanso', 'sec' => $states['break']],
                                ['label' => 'Auxiliar', 'sec' => $states['not_ready']],
                                ['label' => 'Offline', 'sec' => $states['offline']],
                            ] as $item)
                                <div class="flex justify-between p-1.5 bg-slate-50 dark:bg-zinc-900 rounded">
                                    <span>{{ $item['label'] }}</span>
                                    <span class="font-mono">{{ gmdate('H:i:s', $item['sec']) }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between pt-2 border-t dark:border-zinc-800 font-bold">
                                <span>Total</span>
                                <span class="font-mono">{{ gmdate('H:i:s', $states['total_seconds'] - $states['productive_seconds']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Llamadas + Auxiliares --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <flux:card>
                <flux:heading size="sm" class="mb-3">Atención de Llamadas</flux:heading>
                @if($queuePerf->isNotEmpty())
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs text-slate-500 uppercase border-b dark:border-zinc-800">
                            <th class="pb-2 font-semibold">Cola</th>
                            <th class="pb-2 font-semibold text-right">Atendidas</th>
                            <th class="pb-2 font-semibold text-right">AHT</th>
                            <th class="pb-2 font-semibold text-right">Tiempo</th>
                        </tr></thead>
                        <tbody>
                            @foreach($queuePerf as $qp)
                                <tr class="border-t dark:border-zinc-800">
                                    <td class="py-2 font-medium">{{ $qp['queue_name'] ?? '—' }}</td>
                                    <td class="py-2 text-right">{{ $qp['handled'] ?? 0 }}</td>
                                    <td class="py-2 text-right font-mono">{{ $qp['avg_aht'] ? gmdate('i:s', (int) $qp['avg_aht']) : '--:--' }}</td>
                                    <td class="py-2 text-right font-mono">{{ $qp['avg_aht'] ? gmdate('H:i:s', (int) $qp['handled'] * (int) $qp['avg_aht']) : '--:--' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <flux:text class="text-slate-400">Sin datos de llamadas</flux:text>
                @endif
            </flux:card>

            <flux:card>
                <flux:heading size="sm" class="mb-3">Uso de Auxiliares</flux:heading>
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs text-slate-500 uppercase border-b dark:border-zinc-800">
                        <th class="pb-2 font-semibold">Auxiliar</th>
                        <th class="pb-2 font-semibold text-right">Veces</th>
                        <th class="pb-2 font-semibold text-right">Tiempo</th>
                        <th class="pb-2 font-semibold text-center">Estado</th>
                    </tr></thead>
                    <tbody>
                        @php
                            $auxItems = [
                                ['label' => 'Almuerzo', 'sec' => $states['lunch'], 'ok' => $states['lunch'] <= 3300],
                                ['label' => 'Descanso', 'sec' => $states['break'], 'ok' => $states['break'] <= 1200],
                                ['label' => 'Personal', 'sec' => $states['not_ready'], 'ok' => $states['not_ready'] <= 600],
                            ];
                        @endphp
                        @foreach($auxItems as $item)
                            <tr class="border-t dark:border-zinc-800">
                                <td class="py-2 font-medium">{{ $item['label'] }}</td>
                                <td class="py-2 text-right">{{ $item['sec'] > 0 ? 1 : 0 }}</td>
                                <td class="py-2 text-right font-mono">{{ $item['sec'] > 0 ? gmdate('i:s', $item['sec']) : '—' }}</td>
                                <td class="py-2 text-center">{{ $item['ok'] ? '🟢' : '🔴' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </flux:card>
        </div>

        {{-- Detalle de Eventos --}}
        <flux:card>
            <flux:heading size="sm" class="mb-3">Detalle de Eventos</flux:heading>
            @if(isset($transitions) && $transitions->isNotEmpty())
                <div class="max-h-48 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs text-slate-500 uppercase border-b dark:border-zinc-800 sticky top-0 bg-white dark:bg-zinc-900">
                            <th class="pb-2 font-semibold">Hora</th>
                            <th class="pb-2 font-semibold">Evento</th>
                            <th class="pb-2 font-semibold text-right">Duración</th>
                        </tr></thead>
                        <tbody>
                            @foreach($transitions->sortByDesc('transition_time')->take(20) as $t)
                                <tr class="border-t dark:border-zinc-800">
                                    <td class="py-1.5 font-mono text-xs">{{ Carbon\Carbon::parse($t->transition_time)->format('H:i') }}</td>
                                    <td class="py-1.5">
                                        <flux:badge size="sm" color="{{ match(strtoupper($t->agent_state)) { 'READY' => 'green', 'TALKING' => 'blue', 'WORK','ACW' => 'purple', 'LOGOUT','OFFLINE' => 'red', 'NOT_READY' => 'amber', default => 'slate' } }}" inset="top">
                                            {{ strtoupper($t->agent_state) }}
                                        </flux:badge>
                                    </td>
                                    <td class="py-1.5 text-right font-mono text-xs">{{ gmdate('i:s', $t->duration) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <flux:text class="text-slate-400 text-center py-4">Sin eventos registrados</flux:text>
            @endif
        </flux:card>

        {{-- Footer --}}
        <div class="text-xs text-slate-400 text-center pt-4 border-t dark:border-zinc-800">
            Última actualización: {{ now()->diffForHumans() }}
            <span class="mx-2">•</span>
            Fuente: Cisco Finesse • CUIC • WFM • Control de Asistencia
        </div>
    @endif
</div>
