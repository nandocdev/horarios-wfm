<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="chart-bar" variant="mini" class="text-blue-600" />
                Análisis de Staffing
            </flux:heading>
            <flux:subheading>Detección de sobre y sub dimensionaliento</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="queueFilter" size="sm" class="md:w-48">
                <option value="">Todas las colas</option>
                @foreach($queues as $queue)
                    <option value="{{ $queue->id }}">{{ $queue->name }}</option>
                @endforeach
            </flux:select>
            <flux:input type="date" wire:model.live="date" size="sm" />
        </div>
    </div>

    @if($totalIntervals > 0)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <flux:card class="p-3 border-l-4 border-green-500">
                <p class="text-[10px] font-bold text-green-600 uppercase tracking-widest">Adecuados</p>
                <p class="text-2xl font-bold text-green-700 mt-1">{{ $adequateCount }}
                    <span class="text-sm font-normal text-green-500">({{ $adequatePct }}%)</span>
                </p>
                <p class="text-[10px] text-slate-400 mt-0.5">Cobertura suficiente</p>
            </flux:card>
            <flux:card class="p-3 border-l-4 border-red-500">
                <p class="text-[10px] font-bold text-red-600 uppercase tracking-widest">Sub dimensionalos</p>
                <p class="text-2xl font-bold text-red-700 mt-1">{{ $underCount }}
                    <span class="text-sm font-normal text-red-500">({{ $underPct }}%)</span>
                </p>
                <p class="text-[10px] text-slate-400 mt-0.5">Faltan agentes (gap &gt; 0)</p>
            </flux:card>
            <flux:card class="p-3 border-l-4 border-amber-500">
                <p class="text-[10px] font-bold text-amber-600 uppercase tracking-widest">Sobre dimensionalos</p>
                <p class="text-2xl font-bold text-amber-700 mt-1">{{ $overCount }}
                    <span class="text-sm font-normal text-amber-500">({{ $overPct }}%)</span>
                </p>
                <p class="text-[10px] text-slate-400 mt-0.5">Agentes de más (gap &lt; 0)</p>
            </flux:card>
            <flux:card class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cobertura Prom.</p>
                <p class="text-2xl font-bold mt-1 {{ $avgCoverage >= 100 ? 'text-green-600' : ($avgCoverage >= 85 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ $avgCoverage !== null ? number_format($avgCoverage, 1) . '%' : '—' }}
                </p>
            </flux:card>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4">
                <flux:heading size="sm" class="flex items-center gap-2">
                    <flux:icon name="exclamation-triangle" class="size-4 text-red-500" />
                    Sub dimensionalos — máx. gap: {{ number_format($maxGap, 1) }}
                </flux:heading>
                <div class="mt-3 space-y-1">
                    @forelse($understaffed->take(10) as $i)
                        <div class="flex items-center justify-between py-1 px-2 bg-red-50 rounded text-xs">
                            <span class="font-mono">{{ $i->interval_start->format('H:i') }}</span>
                            <span class="font-semibold text-red-600">req {{ number_format($i->required_agents, 1) }} / prog {{ $i->scheduled_agents }} / gap +{{ number_format($i->gap, 1) }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">Sin intervalos sub dimensionalos.</p>
                    @endforelse
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="flex items-center gap-2">
                    <flux:icon name="check-circle" class="size-4 text-amber-500" />
                    Sobre dimensionalos — máx. exceso: {{ number_format($maxOverstaff, 1) }}
                </flux:heading>
                <div class="mt-3 space-y-1">
                    @forelse($overstaffed->take(10) as $i)
                        <div class="flex items-center justify-between py-1 px-2 bg-amber-50 rounded text-xs">
                            <span class="font-mono">{{ $i->interval_start->format('H:i') }}</span>
                            <span class="font-semibold text-amber-600">req {{ number_format($i->required_agents, 1) }} / prog {{ $i->scheduled_agents }} / gap {{ number_format($i->gap, 1) }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">Sin intervalos sobre dimensionalos.</p>
                    @endforelse
                </div>
            </flux:card>
        </div>

        <flux:card class="p-0 overflow-hidden">
            <div class="p-3 border-b border-slate-100 flex items-center justify-between">
                <flux:heading size="sm">Detalle por Intervalo</flux:heading>
                <div class="flex gap-1">
                    <flux:badge color="green" size="sm" class="rounded-md">{{ $adequateCount }} ok</flux:badge>
                    <flux:badge color="red" size="sm" class="rounded-md">{{ $underCount }} sub</flux:badge>
                    <flux:badge color="amber" size="sm" class="rounded-md">{{ $overCount }} sobre</flux:badge>
                </div>
            </div>
            <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 z-10 bg-slate-800 text-white">
                        <tr>
                            <th class="py-1.5 px-2 font-semibold text-center">Hora</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Requeridos</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Programados</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Disponibles</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Cobertura</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Gap</th>
                            <th class="py-1.5 px-2 font-semibold text-center w-24">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($intervals as $i)
                            @php
                                $state = $i->gap > 0 ? 'sub' : ($i->gap < 0 ? 'sobre' : 'ok');
                                $rowColor = match($state) {
                                    'sub' => 'bg-red-50/50 hover:bg-red-100/50',
                                    'sobre' => 'bg-amber-50/50 hover:bg-amber-100/50',
                                    default => 'hover:bg-green-50/50',
                                };
                                $gapColor = $i->gap > 0 ? 'text-red-600 font-bold' : ($i->gap < 0 ? 'text-amber-600' : 'text-slate-400');
                                $covColor = $i->coverage >= 100 ? 'text-green-600 font-semibold' : ($i->coverage >= 85 ? 'text-amber-600' : 'text-red-600 font-semibold');
                                $badgeColor = match($state) { 'sub' => 'red', 'sobre' => 'amber', default => 'green' };
                                $badgeLabel = match($state) { 'sub' => 'Sub', 'sobre' => 'Sobre', default => 'OK' };
                            @endphp
                            <tr class="{{ $rowColor }} transition-colors duration-100">
                                <td class="py-1 px-2 text-center font-mono font-semibold text-slate-700">{{ $i->interval_start->format('H:i') }}</td>
                                <td class="py-1 px-2 text-center font-mono text-slate-800 font-semibold">{{ number_format($i->required_agents, 1) }}</td>
                                <td class="py-1 px-2 text-center font-mono">{{ $i->scheduled_agents }}</td>
                                <td class="py-1 px-2 text-center font-mono">{{ number_format($i->available_agents, 1) }}</td>
                                <td class="py-1 px-2 text-center font-mono {{ $covColor }}">{{ number_format($i->coverage, 1) }}%</td>
                                <td class="py-1 px-2 text-center font-mono {{ $gapColor }}">{{ $i->gap >= 0 ? '+' : '' }}{{ number_format($i->gap, 1) }}</td>
                                <td class="py-1 px-2 text-center">
                                    <flux:badge :color="$badgeColor" size="sm" class="rounded-md">{{ $badgeLabel }}</flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-6 text-center text-slate-400 italic">Sin datos de staffing para esta fecha.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    @else
        <flux:card class="p-8 text-center">
            <flux:icon name="chart-bar" class="size-12 text-slate-300 mx-auto mb-3" />
            <flux:heading>Sin datos de staffing</flux:heading>
            <flux:text class="text-slate-500">No hay requerimientos calculados para esta fecha. Use la sección Staffing para generar los datos.</flux:text>
        </flux:card>
    @endif
</div>
