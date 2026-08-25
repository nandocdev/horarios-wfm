<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    @if($view === 'list')
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
            <div>
                <flux:heading size="xl" level="1" class="flex items-center gap-2">
                    <flux:icon name="chart-bar" variant="mini" class="text-blue-600" />
                    Capacity Planning
                </flux:heading>
                <flux:subheading>Planes de capacidad y análisis de cobertura</flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:button href="{{ route('operations.capacity-analysis') }}" icon="chart-bar" size="sm" variant="ghost" wire:navigate>
                    Análisis
                </flux:button>
                <flux:button wire:click="showGenerate" icon="plus">Nuevo Plan</flux:button>
            </div>
        </div>

        <div class="space-y-4">
            @forelse($plans as $plan)
                <flux:card class="p-4">
                    <div class="flex items-start justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <flux:heading size="lg">{{ $plan->name }}</flux:heading>
                                @php
                                    $stColor = match($plan->status) { 'published' => 'green', 'draft' => 'amber', default => 'slate' };
                                @endphp
                                <flux:badge :color="$stColor" size="sm" class="rounded-md">{{ $plan->status }}</flux:badge>
                            </div>
                            <flux:text size="sm" class="text-slate-500">
                                {{ $plan->plan_date?->format('d/m/Y') ?? '—' }}
                                &middot; {{ $plan->shrinkage_rate }}% shrinkage
                                &middot; {{ $plan->generator?->name ?? '—' }}
                            </flux:text>
                        </div>
                        <flux:button wire:click="selectPlan('{{ $plan->id }}')" size="sm" icon="eye">Ver</flux:button>
                    </div>

                    @if($plan->results->isNotEmpty())
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-4">
                            @foreach($plan->results as $r)
                                <div class="p-2 bg-slate-50 rounded text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Cobertura</p>
                                    <p class="text-lg font-bold {{ $r->avg_coverage >= 100 ? 'text-green-600' : ($r->avg_coverage >= 85 ? 'text-amber-600' : 'text-red-600') }}">
                                        {{ number_format($r->avg_coverage, 1) }}%
                                    </p>
                                </div>
                                <div class="p-2 bg-slate-50 rounded text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Req. Total</p>
                                    <p class="text-lg font-bold text-slate-800">{{ number_format($r->total_staff_required, 1) }}</p>
                                </div>
                                <div class="p-2 bg-slate-50 rounded text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Disp. Total</p>
                                    <p class="text-lg font-bold text-slate-800">{{ number_format($r->total_staff_available, 1) }}</p>
                                </div>
                                <div class="p-2 bg-slate-50 rounded text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Intervalos con Gap</p>
                                    <p class="text-lg font-bold text-red-600">{{ $r->intervals_with_gap }}/{{ $r->total_intervals }}</p>
                                </div>
                                <div class="p-2 bg-slate-50 rounded text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Max Gap</p>
                                    <p class="text-lg font-bold text-red-600">{{ number_format($r->max_gap, 1) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:card>
            @empty
                <flux:card class="p-8 text-center">
                    <flux:icon name="chart-bar" class="size-12 text-slate-300 mx-auto mb-3" />
                    <flux:heading>Sin planes de capacidad</flux:heading>
                    <flux:text class="text-slate-500">Genere el primer plan a partir de un forecast.</flux:text>
                    <flux:button wire:click="showGenerate" icon="plus" class="mt-4">Nuevo Plan</flux:button>
                </flux:card>
            @endforelse

            @if($plans->hasPages())
                <div class="pt-4">{{ $plans->links() }}</div>
            @endif
        </div>

    @elseif($view === 'generate')
        <div class="max-w-lg mx-auto">
            <flux:card>
                <div class="flex items-center gap-3 mb-4">
                    <flux:button wire:click="back" icon="arrow-left" size="sm" variant="ghost"></flux:button>
                    <div>
                        <flux:heading size="lg">Generar Plan de Capacidad</flux:heading>
                        <flux:subheading>Basado en el escenario de forecast activo</flux:subheading>
                    </div>
                </div>

                <flux:separator class="mb-4" />

                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Fecha del Plan</flux:label>
                        <flux:input type="date" wire:model="planDate" />
                        <flux:error name="planDate" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Tasa de Shrinkage (%)</flux:label>
                        <flux:input type="number" wire:model="shrinkageRate" step="0.1" min="0" max="100" />
                        <flux:text size="xs" class="text-slate-500">Porcentaje de tiempo no productivo (breaks, reuniones, etc.)</flux:text>
                        <flux:error name="shrinkageRate" />
                    </flux:field>

                    <flux:button wire:click="generate" icon="play" class="w-full" size="lg">
                        Generar Plan
                    </flux:button>
                </div>
            </flux:card>
        </div>

    @elseif($view === 'detail')
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <flux:button wire:click="back" icon="arrow-left" size="sm" variant="ghost"></flux:button>
                <div>
                    <flux:heading size="xl" level="1" class="flex items-center gap-2">
                        <flux:icon name="chart-bar" variant="mini" class="text-blue-600" />
                        {{ $plan->name }}
                    </flux:heading>
                    <flux:subheading>
                        {{ $plan->plan_date?->format('d/m/Y') ?? '—' }}
                        &middot; {{ $plan->shrinkage_rate }}% shrinkage
                        <flux:badge size="sm" :color="($plan->status === 'published' ? 'green' : 'amber')" class="rounded-md inline ml-2">{{ $plan->status }}</flux:badge>
                    </flux:subheading>
                </div>
                <div class="ml-auto flex gap-2">
                    @if($plan->status === 'draft')
                        <flux:button wire:click="publishPlan('{{ $plan->id }}')" size="sm" icon="check" color="green">Publicar</flux:button>
                    @else
                        <flux:button wire:click="draftPlan('{{ $plan->id }}')" size="sm" icon="archive-box" color="amber">Borrador</flux:button>
                    @endif
                </div>
            </div>

            @if($results->isNotEmpty())
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                    @foreach($results as $r)
                        <flux:card class="p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cobertura</p>
                            <p class="text-xl font-bold {{ $r->avg_coverage >= 100 ? 'text-green-600' : ($r->avg_coverage >= 85 ? 'text-amber-600' : 'text-red-600') }} mt-1">
                                {{ number_format($r->avg_coverage, 1) }}%
                            </p>
                        </flux:card>
                        <flux:card class="p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Staff Req.</p>
                            <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($r->total_staff_required, 1) }}</p>
                        </flux:card>
                        <flux:card class="p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Staff Disp.</p>
                            <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($r->total_staff_available, 1) }}</p>
                        </flux:card>
                        <flux:card class="p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Gap Máximo</p>
                            <p class="text-xl font-bold text-red-600 mt-1">{{ number_format($r->max_gap, 1) }}</p>
                        </flux:card>
                        <flux:card class="p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Intervalos c/Gap</p>
                            <p class="text-xl font-bold text-red-600 mt-1">{{ $r->intervals_with_gap }}/{{ $r->total_intervals }}</p>
                        </flux:card>
                        <flux:card class="p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Intervalos c/Skill Gap</p>
                            <p class="text-xl font-bold text-amber-600 mt-1">{{ $r->intervals_with_skill_gap }}/{{ $r->total_intervals }}</p>
                        </flux:card>
                        <flux:card class="p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Vol. Pron.</p>
                            <p class="text-xl font-bold text-blue-600 mt-1">{{ number_format($intervals->sum('forecast_call_volume')) }}</p>
                        </flux:card>
                    @endforeach
                </div>
            @endif

            <flux:card class="p-0 overflow-hidden">
                <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 z-10 bg-slate-800 text-white">
                            <tr>
                                <th class="py-1.5 px-2 font-semibold text-center">Slot</th>
                                <th class="py-1.5 px-2 font-semibold text-center">Intervalo</th>
                                <th class="py-1.5 px-2 font-semibold text-center">Vol.</th>
                                <th class="py-1.5 px-2 font-semibold text-center">AHT</th>
                                <th class="py-1.5 px-2 font-semibold text-center">Req.</th>
                                <th class="py-1.5 px-2 font-semibold text-center">Prog.</th>
                                <th class="py-1.5 px-2 font-semibold text-center">Disp.</th>
                                <th class="py-1.5 px-2 font-semibold text-center">Skill</th>
                                <th class="py-1.5 px-2 font-semibold text-center">Gap</th>
                                <th class="py-1.5 px-2 font-semibold text-center">S.Gap</th>
                                <th class="py-1.5 px-2 font-semibold text-center">Cobertura</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($rows as $row)
                                @php
                                    $covColor = $row->coverage !== null
                                        ? ($row->coverage >= 100 ? 'text-green-600 font-semibold' : ($row->coverage >= 85 ? 'text-amber-600' : 'text-red-600 font-semibold'))
                                        : 'text-slate-300';
                                    $gapColor = $row->gap !== null
                                        ? ($row->gap > 0 ? 'text-red-600 font-semibold' : 'text-green-600')
                                        : 'text-slate-300';
                                    $sgColor = $row->skill_gap !== null
                                        ? ($row->skill_gap > 0 ? 'text-amber-600 font-semibold' : 'text-green-600')
                                        : 'text-slate-300';
                                @endphp
                                <tr class="hover:bg-blue-50/50 transition-colors duration-100">
                                    <td class="py-1 px-2 text-center font-mono text-slate-400 text-[10px]">{{ $row->slot }}</td>
                                    <td class="py-1 px-2 text-center font-mono font-semibold text-slate-700">{{ $row->label }}</td>
                                    <td class="py-1 px-2 text-center font-mono {{ $row->forecast !== null ? 'text-blue-600' : 'text-slate-300' }}">{{ $row->forecast ?? '—' }}</td>
                                    <td class="py-1 px-2 text-center font-mono text-slate-600">{{ $row->aht ? sprintf('%02d:%02d', floor($row->aht / 60), (int) $row->aht % 60) : '—' }}</td>
                                    <td class="py-1 px-2 text-center font-mono text-slate-800">{{ $row->required !== null ? number_format($row->required, 1) : '—' }}</td>
                                    <td class="py-1 px-2 text-center font-mono text-slate-800">{{ $row->scheduled !== null ? $row->scheduled : '—' }}</td>
                                    <td class="py-1 px-2 text-center font-mono text-slate-800">{{ $row->available !== null ? number_format($row->available, 1) : '—' }}</td>
                                    <td class="py-1 px-2 text-center font-mono text-slate-800">{{ $row->with_skill !== null ? $row->with_skill : '—' }}</td>
                                    <td class="py-1 px-2 text-center font-mono {{ $gapColor }}">{{ $row->gap !== null ? (($row->gap >= 0 ? '+' : '') . number_format($row->gap, 1)) : '—' }}</td>
                                    <td class="py-1 px-2 text-center font-mono {{ $sgColor }}">{{ $row->skill_gap !== null ? (($row->skill_gap > 0 ? '+' : '') . number_format($row->skill_gap, 1)) : '—' }}</td>
                                    <td class="py-1 px-2 text-center font-mono {{ $covColor }}">{{ $row->coverage !== null ? number_format($row->coverage, 1) . '%' : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="p-6 text-center text-slate-400 italic">Sin intervalos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>
    @endif
</div>
