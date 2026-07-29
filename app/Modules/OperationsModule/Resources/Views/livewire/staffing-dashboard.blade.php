<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="users" variant="mini" class="text-blue-600" />
                Staffing
            </flux:heading>
            <flux:subheading>Matriz de requerimientos de personal &middot; {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="queueFilter" size="sm" class="md:w-48">
                <option value="">Todas las colas</option>
                @foreach($queues as $queue)
                    <option value="{{ $queue->id }}">{{ $queue->name }}</option>
                @endforeach
            </flux:select>
            <flux:input type="date" wire:model.live="date" size="sm" />
            <flux:button wire:click="calculate" icon="arrow-path" size="sm" :disabled="!$scenario">
                Calcular
            </flux:button>
        </div>
    </div>

    @unless($scenario)
        <flux:card class="p-8 text-center">
            <flux:icon name="chart-bar" class="size-12 text-slate-300 mx-auto mb-3" />
            <flux:heading>Sin escenario de forecast activo</flux:heading>
            <flux:text class="text-slate-500">Genere un forecast primero en la sección correspondiente.</flux:text>
        </flux:card>
    @else
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Vol. Pronosticado</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($totalForecast) }}</p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Req. Promedio</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $avgRequired !== null ? number_format($avgRequired, 1) : '—' }}</p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Prog. Promedio</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $avgScheduled !== null ? number_format($avgScheduled, 1) : '—' }}</p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cobertura Prom.</p>
                <p class="text-2xl font-bold mt-1 {{ $avgCoverage !== null ? ($avgCoverage >= 100 ? 'text-green-600' : ($avgCoverage >= 85 ? 'text-amber-600' : 'text-red-600')) : 'text-slate-400' }}">
                    {{ $avgCoverage !== null ? number_format($avgCoverage, 1) . '%' : '—' }}
                </p>
            </flux:card>
        </div>

        <flux:card class="p-0 overflow-hidden">
            <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 z-10 bg-slate-800 text-white">
                        <tr>
                            <th class="py-1.5 px-2 font-semibold text-center w-16">Slot</th>
                            <th class="py-1.5 px-2 font-semibold text-center w-20">Intervalo</th>
                            <th class="py-1.5 px-2 font-semibold text-center w-16">Forecast</th>
                            <th class="py-1.5 px-2 font-semibold text-center w-16">Requeridos</th>
                            <th class="py-1.5 px-2 font-semibold text-center w-16">Programados</th>
                            <th class="py-1.5 px-2 font-semibold text-center w-16">Disponibles</th>
                            <th class="py-1.5 px-2 font-semibold text-center w-16">Gap</th>
                            <th class="py-1.5 px-2 font-semibold text-center w-20">Cobertura</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            @php
                                $gapColor = 'text-slate-400';
                                $covColor = 'text-slate-400';
                                if ($row->gap !== null) {
                                    $gapColor = $row->gap > 0 ? 'text-red-600 font-semibold' : ($row->gap < 0 ? 'text-green-600' : 'text-slate-500');
                                }
                                if ($row->coverage !== null) {
                                    $covColor = $row->coverage >= 100 ? 'text-green-600 font-semibold' : ($row->coverage >= 85 ? 'text-amber-600' : 'text-red-600 font-semibold');
                                }
                            @endphp
                            <tr class="hover:bg-blue-50/50 transition-colors duration-100">
                                <td class="py-1 px-2 text-center font-mono text-slate-400 text-[10px]">{{ $row->slot }}</td>
                                <td class="py-1 px-2 text-center font-mono font-semibold text-slate-700">{{ $row->label }}</td>
                                <td class="py-1 px-2 text-center font-mono {{ $row->forecast !== null ? 'text-blue-600' : 'text-slate-300' }}">
                                    {{ $row->forecast !== null ? number_format($row->forecast) : '—' }}
                                </td>
                                <td class="py-1 px-2 text-center font-mono {{ $row->required !== null ? 'text-slate-800 font-semibold' : 'text-slate-300' }}">
                                    {{ $row->required !== null ? number_format($row->required, 1) : '—' }}
                                </td>
                                <td class="py-1 px-2 text-center font-mono {{ $row->scheduled !== null ? 'text-slate-800' : 'text-slate-300' }}">
                                    {{ $row->scheduled !== null ? number_format($row->scheduled) : '—' }}
                                </td>
                                <td class="py-1 px-2 text-center font-mono {{ $row->available !== null ? 'text-slate-800' : 'text-slate-300' }}">
                                    {{ $row->available !== null ? number_format($row->available, 1) : '—' }}
                                </td>
                                <td class="py-1 px-2 text-center font-mono {{ $gapColor }}">
                                    {{ $row->gap !== null ? ($row->gap >= 0 ? '+' : '') . number_format($row->gap, 1) : '—' }}
                                </td>
                                <td class="py-1 px-2 text-center font-mono {{ $covColor }}">
                                    {{ $row->coverage !== null ? number_format($row->coverage, 1) . '%' : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-6 text-center text-slate-400 italic">
                                    Sin datos de staffing. Presione "Calcular" para generarlos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif
</div>
