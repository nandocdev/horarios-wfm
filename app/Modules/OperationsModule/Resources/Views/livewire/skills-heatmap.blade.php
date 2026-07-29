<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="chart-bar" variant="mini" class="text-blue-600" />
                Cobertura de Skills
            </flux:heading>
            <flux:subheading>Distribución y brechas por skill y cola</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Skills Activos</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $skillCount }}</p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Agentes Activos</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $employeeCount }}</p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Asignaciones</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $skillDistribution->sum('count') }}</p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nivel Prom.</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $skillDistribution->avg('avg_level') ? number_format($skillDistribution->avg('avg_level'), 1) : '—' }}</p>
        </flux:card>
    </div>

    <div class="flex items-center gap-2">
        <flux:select wire:model.live="queueFilter" size="sm" class="md:w-56">
            <option value="">Todas las colas</option>
            @foreach($queues as $queue)
                <option value="{{ $queue->id }}">{{ $queue->name }}</option>
            @endforeach
        </flux:select>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <div class="p-3 border-b border-slate-100">
            <flux:heading size="sm">Distribución de Skills por Agente</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-800 text-white text-[10px] uppercase tracking-widest">
                    <tr>
                        <th class="py-1.5 px-2 font-semibold">Skill</th>
                        <th class="py-1.5 px-2 font-semibold text-center">Agentes</th>
                        <th class="py-1.5 px-2 font-semibold text-center">% Agentes</th>
                        <th class="py-1.5 px-2 font-semibold text-center">Nivel Prom.</th>
                        <th class="py-1.5 px-2 font-semibold text-center">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($allSkills as $skill)
                        @php
                            $dist = $skillDistribution->get($skill->id);
                            $count = $dist?->count ?? 0;
                            $avgLevel = $dist?->avg_level ?? 0;
                            $pct = $employeeCount > 0 ? round(($count / $employeeCount) * 100, 1) : 0;
                            $covColor = $pct >= 50 ? 'text-green-600' : ($pct >= 25 ? 'text-amber-600' : 'text-red-600');
                            $barColor = $pct >= 50 ? 'bg-green-500' : ($pct >= 25 ? 'bg-amber-500' : 'bg-red-500');
                        @endphp
                        <tr class="hover:bg-blue-50/50">
                            <td class="py-1.5 px-2 font-semibold text-slate-700">{{ $skill->name }}</td>
                            <td class="py-1.5 px-2 text-center font-mono font-semibold">{{ $count }}</td>
                            <td class="py-1.5 px-2">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $barColor }}" style="width: {{ min(100, $pct) }}%"></div>
                                    </div>
                                    <span class="font-mono text-xs {{ $covColor }} w-10 text-right">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td class="py-1.5 px-2 text-center font-mono">{{ number_format($avgLevel, 1) }}</td>
                            <td class="py-1.5 px-2 text-center">
                                <flux:badge size="sm" class="rounded-md" :color="$pct >= 50 ? 'green' : ($pct >= 25 ? 'amber' : 'red')">
                                    {{ $pct >= 50 ? 'Cubierto' : ($pct >= 25 ? 'Parcial' : 'Crítico') }}
                                </flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-slate-400 italic">Sin skills registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>

    @if($coverageDetails->isNotEmpty())
        <flux:card class="p-0 overflow-hidden">
            <div class="p-3 border-b border-slate-100">
                <flux:heading size="sm">Cobertura por Cola y Skill</flux:heading>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-800 text-white text-[10px] uppercase tracking-widest">
                        <tr>
                            <th class="py-1.5 px-2 font-semibold">Cola</th>
                            <th class="py-1.5 px-2 font-semibold">Skill</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Nivel Min.</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Requeridos</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Disponibles</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Cobertura</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Déficit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($coverageDetails as $cd)
                            @php
                                $covColor = $cd['coverage'] >= 100 ? 'text-green-600' : ($cd['coverage'] >= 75 ? 'text-amber-600' : 'text-red-600');
                                $covBg = $cd['coverage'] >= 100 ? 'bg-green-100' : ($cd['coverage'] >= 75 ? 'bg-amber-100' : 'bg-red-100');
                            @endphp
                            <tr class="hover:bg-blue-50/50">
                                <td class="py-1.5 px-2 font-semibold text-slate-700">{{ $cd['queue_name'] }}</td>
                                <td class="py-1.5 px-2">{{ $cd['skill_name'] }}</td>
                                <td class="py-1.5 px-2 text-center font-mono">{{ $cd['minimum_level'] }}</td>
                                <td class="py-1.5 px-2 text-center font-mono">{{ $cd['required_count'] }}</td>
                                <td class="py-1.5 px-2 text-center font-mono">{{ $cd['available_count'] }}</td>
                                <td class="py-1.5 px-2 text-center">
                                    <span class="px-2 py-0.5 rounded-full font-mono font-semibold text-xs {{ $covBg }} {{ $covColor }}">
                                        {{ number_format($cd['coverage'], 1) }}%
                                    </span>
                                </td>
                                <td class="py-1.5 px-2 text-center font-mono {{ $cd['gap'] > 0 ? 'text-red-600 font-bold' : 'text-green-600' }}">
                                    @if($cd['gap'] > 0)
                                        +{{ $cd['gap'] }}
                                    @else
                                        0
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif

    @if(!empty($queueSummary))
        <flux:card class="p-0 overflow-hidden">
            <div class="p-3 border-b border-slate-100">
                <flux:heading size="sm">Resumen por Cola</flux:heading>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-800 text-white text-[10px] uppercase tracking-widest">
                        <tr>
                            <th class="py-1.5 px-2 font-semibold">Cola</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Skills Requeridos</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Cubiertos</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Con Déficit</th>
                            <th class="py-1.5 px-2 font-semibold text-center">Cobertura Gral.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($queueSummary as $qs)
                            @php
                                $qCovColor = $qs['overall_coverage'] >= 100 ? 'text-green-600' : ($qs['overall_coverage'] >= 75 ? 'text-amber-600' : 'text-red-600');
                            @endphp
                            <tr class="hover:bg-blue-50/50">
                                <td class="py-1.5 px-2 font-semibold text-slate-700">{{ $qs['queue_name'] }}</td>
                                <td class="py-1.5 px-2 text-center font-mono">{{ $qs['total_skills'] }}</td>
                                <td class="py-1.5 px-2 text-center font-mono text-green-600">{{ $qs['skills_covered'] }}</td>
                                <td class="py-1.5 px-2 text-center font-mono text-red-600">{{ $qs['skills_with_gap'] }}</td>
                                <td class="py-1.5 px-2 text-center font-mono font-semibold {{ $qCovColor }}">{{ number_format($qs['overall_coverage'], 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif
</div>
