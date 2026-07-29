<div class="py-2 px-4 space-y-8 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="phone" variant="mini" class="text-blue-600" />
                Dashboard de Colas
            </flux:heading>
            <flux:subheading>
                @if($selectedQueue)
                    Monitoreo {{ $selectedQueue->name }} — {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                @else
                    Resumen de rendimiento por cola — {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                @endif
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="queueId" size="sm" class="md:w-56">
                <option value="">Todas las colas</option>
                @foreach($queues as $queue)
                    <option value="{{ $queue->id }}">{{ $queue->name }}</option>
                @endforeach
            </flux:select>
            <flux:input type="date" wire:model.live="date" size="sm" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-50 rounded-md">
                    <flux:icon name="phone-arrow-up-right" class="size-5 text-blue-600" />
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Ofrecidas</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($totalOffered) }}</p>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-50 rounded-md">
                    <flux:icon name="check-circle" class="size-5 text-green-600" />
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Atendidas</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($totalHandled) }}</p>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-50 rounded-md">
                    <flux:icon name="x-circle" class="size-5 text-red-600" />
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Abandonadas</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($totalAbandoned) }}</p>
                    @if($totalOffered > 0)
                        <p class="text-[10px] text-red-500 mt-0.5">{{ round(($totalAbandoned / $totalOffered) * 100, 1) }}% de abandono</p>
                    @endif
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-50 rounded-md">
                    <flux:icon name="clock" class="size-5 text-amber-600" />
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">SLA Promedio</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">
                        @php
                            $slCount = $stats->sum('sl_count');
                            $slPct = $totalOffered > 0 ? round(($slCount / $totalOffered) * 100, 1) : 0;
                        @endphp
                        {{ $slPct }}%
                    </p>
                </div>
            </div>
        </flux:card>
    </div>

    @if($selectedQueue)
        @php
            $realtime = $realtimeStats->firstWhere('csq_name', $selectedQueue->name);
        @endphp
        @if($realtime)
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                <flux:card class="p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Agentes Conectados</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">{{ $realtime->agents_logged_on }}</p>
                </flux:card>
                <flux:card class="p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Hablando</p>
                    <p class="text-xl font-bold text-blue-600 mt-1">{{ $realtime->agents_talking }}</p>
                </flux:card>
                <flux:card class="p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Disponibles</p>
                    <p class="text-xl font-bold text-green-600 mt-1">{{ $realtime->agents_ready }}</p>
                </flux:card>
                <flux:card class="p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">No Disponibles</p>
                    <p class="text-xl font-bold text-amber-600 mt-1">{{ $realtime->agents_not_ready }}</p>
                </flux:card>
                <flux:card class="p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">ACW</p>
                    <p class="text-xl font-bold text-slate-600 mt-1">{{ $realtime->agents_after_call_work }}</p>
                </flux:card>
                <flux:card class="p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Llamadas en Espera</p>
                    <p class="text-xl font-bold text-red-600 mt-1">{{ $realtime->calls_waiting }}</p>
                </flux:card>
                <flux:card class="p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">SLA Tiempo Real</p>
                    <p class="text-xl font-bold mt-1 {{ $realtime->service_level_long_term < 80 ? 'text-red-600' : ($realtime->service_level_long_term < 90 ? 'text-amber-600' : 'text-green-600') }}">
                        {{ number_format($realtime->service_level_long_term, 1) }}%
                    </p>
                </flux:card>
            </div>
        @else
            <flux:card class="p-4 text-center text-slate-400 italic">
                No hay datos en tiempo real disponibles para esta cola.
            </flux:card>
        @endif
    @endif

    <flux:card class="p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="sticky top-0 z-10 bg-slate-50 text-[10px] font-semibold text-slate-500 uppercase tracking-widest border-b">
                    <tr>
                        <th class="py-2 px-4">Cola</th>
                        <th class="py-2 px-4 text-center">Ofrecidas</th>
                        <th class="py-2 px-4 text-center">Atendidas</th>
                        <th class="py-2 px-4 text-center">Abandono %</th>
                        <th class="py-2 px-4 text-center">SLA (20s)</th>
                        <th class="py-2 px-4 text-center">ASA</th>
                        <th class="py-2 px-4 text-center">AHT Real</th>
                        <th class="py-2 px-4 text-center">Meta AHT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($stats as $s)
                        @php
                            $abandonPct = $s->total_offered > 0 ? ($s->abandoned / $s->total_offered) * 100 : 0;
                            $slPct = $s->total_offered > 0 ? ($s->sl_count / $s->total_offered) * 100 : 0;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors duration-150">
                            <td class="py-2 px-4 font-semibold text-slate-700">{{ $s->queue_name }}</td>
                            <td class="py-2 px-4 text-center font-bold">{{ number_format($s->total_offered) }}</td>
                            <td class="py-2 px-4 text-center text-green-600 font-semibold">{{ number_format($s->handled) }}</td>
                            <td class="py-2 px-4 text-center">
                                <span class="{{ $abandonPct > 10 ? 'text-red-600 font-bold' : 'text-slate-500' }}">
                                    {{ number_format($abandonPct, 1) }}%
                                </span>
                            </td>
                            <td class="py-2 px-4 text-center">
                                <flux:badge :color="$slPct < 80 ? 'red' : ($slPct < 90 ? 'amber' : 'green')" variant="subtle" class="rounded-md">
                                    {{ number_format($slPct, 1) }}%
                                </flux:badge>
                            </td>
                            <td class="py-2 px-4 text-center font-mono">
                                {{ $s->avg_asa !== null ? sprintf('%02d:%02d', floor($s->avg_asa / 60), $s->avg_asa % 60) : '—' }}
                            </td>
                            <td class="py-2 px-4 text-center font-mono {{ $s->avg_aht > $s->aht_goal ? 'text-amber-500 font-semibold' : 'text-slate-600' }}">
                                {{ $s->avg_aht !== null ? sprintf('%02d:%02d', floor($s->avg_aht / 60), $s->avg_aht % 60) : '—' }}
                            </td>
                            <td class="py-2 px-4 text-center text-slate-400 italic">
                                {{ $s->aht_goal ? sprintf('%02d:%02d', floor($s->aht_goal / 60), $s->aht_goal % 60) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400 italic">
                                No se encontraron registros para esta fecha.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>
</div>
