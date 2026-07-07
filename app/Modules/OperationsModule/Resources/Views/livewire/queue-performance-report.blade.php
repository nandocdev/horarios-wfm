<div class="py-2 px-4 space-y-8 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gapy-2 px-4 bg-white py-2 px-4 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="phone" variant="mini" class="text-blue-600" />
                Performance por Cola (Capa 3)
            </flux:heading>
            <flux:subheading>Análisis transaccional de tráfico, niveles de servicio y abandono.</flux:subheading>
        </div>

        <div class="flex items-center gapy-2 px-4">
            <flux:input type="date" wire:model.live="date" />
            <flux:button variant="ghost" icon="arrow-down-tray">Exportar</flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gapy-2 px-4">
        <flux:card>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Ofrecidas</p>
            <p class="text-4xl font-bold text-slate-800 mt-2">{{ number_format($stats->sum('total_offered')) }}</p>
        </flux:card>
        <flux:card>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Atendidas (Global)</p>
            <p class="text-4xl font-bold text-green-600 mt-2">{{ number_format($stats->sum('handled')) }}</p>
        </flux:card>
        <flux:card>
            <p class="text-xs font-bold text-red-600 uppercase tracking-widest">Abandono (Global)</p>
            <p class="text-4xl font-bold text-red-600 mt-2">{{ number_format($stats->sum('abandoned')) }}</p>
            <p class="text-[10px] text-red-500 mt-2 italic">
                {{ $stats->sum('total_offered') > 0 ? round(($stats->sum('abandoned') / $stats->sum('total_offered')) * 100, 1) : 0 }}% de abandono
            </p>
        </flux:card>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="sticky top-0 z-10 bg-slate-50 text-[10px] font-semibold text-slate-500 uppercase tracking-widest border-b">
                    <tr>
                        <th class="py-2 px-4">Nombre de la Cola</th>
                        <th class="py-2 px-4 text-center">Ofrecidas</th>
                        <th class="py-2 px-4 text-center">Atendidas</th>
                        <th class="py-2 px-4 text-center">Abandono %</th>
                        <th class="py-2 px-4 text-center">SLA (20s)</th>
                        <th class="py-2 px-4 text-center">ASA (Espera)</th>
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
                                <flux:badge :color="$slPct < 80 ? 'red' : 'green'" variant="subtle" class="rounded-md">
                                    {{ number_format($slPct, 1) }}%
                                </flux:badge>
                            </td>
                            <td class="py-2 px-4 text-center font-mono">
                                {{ sprintf('%02d:%02d', floor($s->avg_asa / 60), $s->avg_asa % 60) }}
                            </td>
                            <td class="py-2 px-4 text-center font-mono {{ $s->avg_aht > $s->aht_goal ? 'text-amber-500 font-semibold' : 'text-slate-600' }}">
                                {{ sprintf('%02d:%02d', floor($s->avg_aht / 60), $s->avg_aht % 60) }}
                            </td>
                            <td class="py-2 px-4 text-center text-slate-400 italic">
                                {{ sprintf('%02d:%02d', floor($s->aht_goal / 60), $s->aht_goal % 60) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400 italic">
                                No se encontraron registros de llamadas para esta fecha.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>
</div>
