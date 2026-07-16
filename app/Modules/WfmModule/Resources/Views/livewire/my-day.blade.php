<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white dark:bg-zinc-900 rounded-xl p-4 border border-zinc-200 dark:border-zinc-800">
        <div class="flex items-center gap-3">
            <flux:avatar size="md" />
            <div>
                <flux:heading size="lg">{{ $employeeData['name'] ?? 'Mi Jornada' }}</flux:heading>
                <flux:text size="sm" class="text-slate-500">{{ $employeeData['team'] ?? '' }}</flux:text>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <flux:text size="sm" class="font-medium">{{ now()->locale('es')->translatedFormat('l d F Y') }}</flux:text>
            <flux:badge color="{{ ($employeeData['is_connected'] ?? false) ? 'green' : 'red' }}" size="sm" inset="top" class="font-bold">
                {{ $employeeData['current_state'] ?? 'OFFLINE' }}
            </flux:badge>
        </div>
    </div>

    @if(!$employeeData)
        <flux:card><div class="text-center py-12 text-slate-400">Seleccione un empleado para ver su jornada</div></flux:card>
    @else
        @php $d = $employeeData; @endphp
        {{-- Summary Bar --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">Turno</flux:text>
                <flux:heading size="lg" class="mt-1">{{ $d['scheduled_entry'] }} - {{ $d['scheduled_end'] }}</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">Entrada</flux:text>
                <flux:heading size="lg" class="mt-1">{{ $d['real_entry'] ?? '--:--' }}</flux:heading>
                @if($d['entry_diff'] !== null)
                    <flux:text size="xs" class="{{ $d['entry_diff'] <= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $d['entry_diff'] <= 0 ? (string) $d['entry_diff'] : '+'.$d['entry_diff'] }} min
                    </flux:text>
                @endif
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">Tiempo Conectado</flux:text>
                <flux:heading size="lg" class="mt-1 font-mono">{{ gmdate('H:i:s', $d['total_seconds']) }}</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">T. Productivo</flux:text>
                <flux:heading size="lg" class="mt-1 font-mono">{{ gmdate('H:i:s', $d['productive_seconds']) }}</flux:heading>
            </flux:card>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">Adherencia</flux:text>
                <flux:heading size="xl" class="mt-1 font-bold text-blue-600">{{ $d['adherence'] }}</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">Ocupación</flux:text>
                <flux:heading size="xl" class="mt-1 font-bold">{{ $d['occupancy'] }}%</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">T. Conectado</flux:text>
                <flux:heading size="xl" class="mt-1 font-mono">{{ gmdate('H:i', $d['total_seconds']) }}</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">T. Productivo</flux:text>
                <flux:heading size="xl" class="mt-1 font-mono">{{ gmdate('H:i', $d['productive_seconds']) }}</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">Llamadas</flux:text>
                <flux:heading size="xl" class="mt-1 font-bold">{{ $d['total_calls'] }}</flux:heading>
                <flux:text size="xs" class="text-slate-400">SLA {{ $d['sla'] }}%</flux:text>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="xs" class="text-slate-500 uppercase font-bold">Shrinkage</flux:text>
                <flux:heading size="xl" class="mt-1 font-mono">{{ round(($d['total_seconds'] - $d['productive_seconds']) / max($d['total_seconds'], 1) * 100, 1) }}%</flux:heading>
            </flux:card>
        </div>

        {{-- Timeline + Eventos --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Timeline --}}
            <div class="lg:col-span-8">
                <flux:card class="p-4 h-[28rem] flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="sm">Línea de Tiempo</flux:heading>
                        <flux:badge color="slate" size="sm">Hoy</flux:badge>
                    </div>
                    <div class="flex-1 min-h-0 overflow-hidden">
                        @livewire('operations.agent-timeline', ['employeeId' => $targetEmployee->id], key('timeline-'.$targetEmployee->id))
                    </div>
                </flux:card>
            </div>

            {{-- Detalle de Eventos --}}
            <div class="lg:col-span-4 space-y-6">
                <flux:card class="p-4 h-[28rem] flex flex-col">
                    <flux:heading size="sm" class="mb-3">Eventos Recientes</flux:heading>
                    <div class="flex-1 overflow-y-auto">
                        <div class="space-y-1">
                            @forelse($d['transitions'] as $t)
                                <div class="flex items-center gap-2 p-1.5 text-xs hover:bg-slate-50 dark:hover:bg-zinc-900 rounded">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0
                                        {{ match(strtoupper($t['agent_state'] ?? '')) {
                                            'READY' => 'bg-green-500',
                                            'TALKING' => 'bg-blue-500',
                                            'WORK', 'ACW' => 'bg-purple-500',
                                            'NOT_READY' => 'bg-amber-500',
                                            'LOGOUT', 'OFFLINE' => 'bg-red-500',
                                            default => 'bg-slate-300',
                                        } }}"></span>
                                    <span class="font-mono text-slate-400 w-10">{{ \Carbon\Carbon::parse($t['transition_time'])->format('H:i') }}</span>
                                    <span class="font-medium flex-1">{{ strtoupper($t['agent_state'] ?? '') }}</span>
                                    <span class="font-mono text-slate-400">{{ gmdate('i:s', $t['duration'] ?? 0) }}</span>
                                </div>
                            @empty
                                <flux:text class="text-slate-400 text-center py-8">Sin eventos</flux:text>
                            @endforelse
                        </div>
                    </div>
                </flux:card>
            </div>
        </div>

        {{-- Cumplimiento + Estado Actual --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Cumplimiento --}}
            <flux:card class="lg:col-span-2">
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
                            $entryDiff = $d['entry_diff'];
                            $entryLabel = $entryDiff !== null ? ($entryDiff <= 0 ? (string) $entryDiff : '+'.$entryDiff) : '—';
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
                            <tr class="border-t dark:border-zinc-800">
                                <td class="py-2 font-medium">{{ $c['label'] }}</td>
                                <td class="py-2 text-center font-mono">{{ $c['sched'] }}</td>
                                <td class="py-2 text-center font-mono">{{ $c['real'] }}</td>
                                <td class="py-2 text-right font-mono {{ $c['ok'] ? 'text-green-600' : 'text-red-600' }}">{{ $c['diff'] }}</td>
                                <td class="py-2 text-center">{{ $c['ok'] ? '🟢' : '🔴' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($d['has_exceptions'])
                    <flux:badge color="amber" size="sm" class="mt-3">Con excepción de horario</flux:badge>
                @endif
            </flux:card>

            {{-- Estado Actual --}}
            <flux:card>
                <div class="space-y-4">
                    <flux:heading size="sm">Estado Actual</flux:heading>
                    <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-zinc-900 rounded-md">
                        <span class="w-3 h-3 rounded-full {{ $d['is_connected'] ? 'bg-green-500' : 'bg-red-400' }}"></span>
                        <div>
                            <flux:text size="lg" class="font-bold">{{ $d['current_state'] }}</flux:text>
                            @if($d['reason'])
                                <flux:text size="xs" class="text-slate-400">{{ $d['reason'] }}</flux:text>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between p-1.5 bg-slate-50 dark:bg-zinc-900 rounded">
                            <span>TALKING</span><span class="font-mono">{{ gmdate('H:i:s', $d['talk']) }}</span>
                        </div>
                        <div class="flex justify-between p-1.5 bg-slate-50 dark:bg-zinc-900 rounded">
                            <span>READY</span><span class="font-mono">{{ gmdate('H:i:s', $d['ready']) }}</span>
                        </div>
                        <div class="flex justify-between p-1.5 bg-slate-50 dark:bg-zinc-900 rounded">
                            <span>ALMUERZO</span><span class="font-mono">{{ gmdate('H:i:s', $d['lunch']) }}</span>
                        </div>
                        <div class="flex justify-between p-1.5 bg-slate-50 dark:bg-zinc-900 rounded">
                            <span>DESCANSO</span><span class="font-mono">{{ gmdate('H:i:s', $d['break']) }}</span>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>
    @endif
</div>
