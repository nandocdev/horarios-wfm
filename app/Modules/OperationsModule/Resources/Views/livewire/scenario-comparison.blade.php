<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="arrow-right-circle" variant="mini" class="text-blue-600" />
                Comparación de Escenarios
            </flux:heading>
            <flux:subheading>Compare múltiples escenarios de forecast lado a lado</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        @foreach($groups as $group)
            <flux:card class="p-3">
                <flux:heading size="sm">{{ $group->name }}</flux:heading>
                <flux:text size="xs" class="text-slate-500">{{ $group->versions->count() }} versión(es)</flux:text>

                @if($group->versions->isNotEmpty())
                    <div class="mt-2 space-y-1">
                        @foreach($group->versions as $v)
                            <button
                                wire:click="selectVersion('{{ $v->id }}')"
                                class="w-full text-left px-2 py-1 text-xs rounded {{ $selectedVersionId === $v->id ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-slate-100 text-slate-600' }}"
                            >
                                {{ $v->name }}
                                <span class="text-[10px] text-slate-400 ml-1">({{ $v->scenarios->count() }} escenarios)</span>
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-400 mt-2 italic">Sin versiones</p>
                @endif
            </flux:card>
        @endforeach
    </div>

    @if($version)
        <div class="space-y-6">
            <flux:card class="p-4">
                <flux:heading size="lg">{{ $version->name }}</flux:heading>
                <flux:text size="sm" class="text-slate-500">
                    Grupo: {{ $version->group->name }} &middot; v{{ $version->version_number }}
                </flux:text>
            </flux:card>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-800 text-white text-[10px] uppercase tracking-widest">
                        <tr>
                            <th class="py-2 px-3 font-semibold">Escenario</th>
                            <th class="py-2 px-3 font-semibold text-center">Tipo</th>
                            <th class="py-2 px-3 font-semibold text-center">Multiplicador</th>
                            <th class="py-2 px-3 font-semibold text-center">Vol. Total</th>
                            <th class="py-2 px-3 font-semibold text-center">AHT Prom.</th>
                            <th class="py-2 px-3 font-semibold text-center">Staff Total</th>
                            <th class="py-2 px-3 font-semibold text-center">Intervalos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($dailyTotals as $scenarioId => $totals)
                            @php
                                $colors = ['base' => 'blue', 'optimistic' => 'green', 'pessimistic' => 'red', 'custom' => 'amber'];
                                $color = $colors[$totals['type']] ?? 'slate';
                            @endphp
                            <tr class="hover:bg-blue-50/50 transition-colors duration-150">
                                <td class="py-2 px-3 font-semibold text-slate-700">{{ $totals['name'] }}</td>
                                <td class="py-2 px-3 text-center">
                                    <flux:badge :color="$color" size="sm" class="rounded-md">{{ $totals['type'] }}</flux:badge>
                                </td>
                                <td class="py-2 px-3 text-center font-mono">{{ number_format($totals['multiplier'], 2) }}x</td>
                                <td class="py-2 px-3 text-center font-mono font-bold {{ $scenarios->count() > 1 && $totals['total_volume'] > $scenarios->first(fn($s) => $s->id !== $scenarioId)?->intervals->sum('call_volume_forecast') * 1.1 ? 'text-green-600' : 'text-slate-800' }}">
                                    {{ number_format($totals['total_volume']) }}
                                </td>
                                <td class="py-2 px-3 text-center font-mono">{{ $totals['avg_aht'] ? sprintf('%02d:%02d', floor($totals['avg_aht'] / 60), (int) $totals['avg_aht'] % 60) : '—' }}</td>
                                <td class="py-2 px-3 text-center font-mono">{{ number_format($totals['total_staff'], 1) }}</td>
                                <td class="py-2 px-3 text-center font-mono text-slate-500">{{ $totals['interval_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(!empty($intervalSlots))
                <flux:card class="p-0 overflow-hidden">
                    <div class="p-3 border-b border-slate-100">
                        <flux:heading size="sm">Detalle por Intervalo</flux:heading>
                    </div>
                    <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="sticky top-0 z-10 bg-slate-800 text-white">
                                <tr>
                                    <th class="py-1.5 px-2 font-semibold text-center w-12">#</th>
                                    <th class="py-1.5 px-2 font-semibold text-center w-24">Intervalo</th>
                                    @foreach($scenarios as $s)
                                        <th colspan="3" class="py-1.5 px-2 font-semibold text-center border-l border-slate-600">
                                            {{ $s->name }}
                                            <span class="text-[9px] block opacity-70">Vol / AHT / Staff</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($intervalSlots as $slot)
                                    <tr class="hover:bg-blue-50/50">
                                        <td class="py-1 px-2 text-center font-mono text-slate-400 text-[10px]">{{ $slot['slot'] }}</td>
                                        <td class="py-1 px-2 text-center font-mono text-slate-700">{{ $slot['label'] }}</td>
                                        @foreach($scenarios as $s)
                                            @php
                                                $vol = $slot["vol_{$s->id}"] ?? null;
                                                $aht = $slot["aht_{$s->id}"] ?? null;
                                                $staff = $slot["staff_{$s->id}"] ?? null;
                                            @endphp
                                            <td class="py-1 px-1.5 text-center font-mono border-l border-slate-100 {{ $vol !== null ? 'text-blue-600' : 'text-slate-300' }}">
                                                {{ $vol ?? '—' }}
                                            </td>
                                            <td class="py-1 px-1.5 text-center font-mono text-slate-600">
                                                {{ $aht ? sprintf('%02d:%02d', floor($aht / 60), (int) $aht % 60) : '—' }}
                                            </td>
                                            <td class="py-1 px-1.5 text-center font-mono text-slate-600">
                                                {{ $staff !== null ? number_format($staff, 1) : '—' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </flux:card>
            @endif
        </div>
    @else
        <flux:card class="p-8 text-center">
            <flux:icon name="arrow-right-circle" class="size-12 text-slate-300 mx-auto mb-3" />
            <flux:heading>Seleccione una versión</flux:heading>
            <flux:text class="text-slate-500">Elija un grupo y versión de forecast para comparar sus escenarios.</flux:text>
        </flux:card>
    @endif
</div>
