<div class="py-2 px-4 space-y-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 bg-white py-2 rounded-md shadow-sm border border-slate-200">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                <flux:icon name="chart-bar" variant="mini" class="text-blue-600" />
                Análisis de Capacidad
            </flux:heading>
            <flux:subheading>Panorama general de planes de capacidad</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Planes</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $totalPlans }}</p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Colas Analizadas</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $totalQueues }}</p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cobertura Prom.</p>
            <p class="text-2xl font-bold mt-1 {{ $avgCoverage >= 100 ? 'text-green-600' : ($avgCoverage >= 85 ? 'text-amber-600' : 'text-red-600') }}">
                {{ $avgCoverage ? number_format($avgCoverage, 1) . '%' : '—' }}
            </p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Staff Req. Total</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $totalRequired ? number_format($totalRequired, 1) : '—' }}</p>
        </flux:card>
        <flux:card class="p-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Intervalos con Gap</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $gapPct }}%</p>
        </flux:card>
    </div>

    @if($coverageTrend->isNotEmpty())
        <flux:card class="p-4">
            <flux:heading size="sm">Tendencia de Cobertura</flux:heading>
            <div class="mt-3 space-y-1.5">
                @foreach($coverageTrend as $date => $cov)
                    @php
                        $barColor = $cov >= 100 ? 'bg-green-500' : ($cov >= 85 ? 'bg-amber-500' : 'bg-red-500');
                        $textColor = $cov >= 100 ? 'text-green-600' : ($cov >= 85 ? 'text-amber-600' : 'text-red-600');
                    @endphp
                    <div class="flex items-center gap-3 text-xs">
                        <span class="w-16 font-mono font-semibold text-slate-600">{{ $date }}</span>
                        <div class="flex-1 h-4 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $barColor }}" style="width: {{ min(100, $cov) }}%"></div>
                        </div>
                        <span class="w-16 text-right font-mono font-semibold {{ $textColor }}">{{ number_format($cov, 1) }}%</span>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    <div class="space-y-4">
        <flux:heading size="sm">Últimos Planes</flux:heading>

        @forelse($latestPlans as $plan)
            <flux:card class="p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <flux:heading size="sm">{{ $plan->name }}</flux:heading>
                            @php $stColor = match($plan->status) { 'published' => 'green', default => 'amber' }; @endphp
                            <flux:badge :color="$stColor" size="sm" class="rounded-md">{{ $plan->status }}</flux:badge>
                        </div>
                        <flux:text size="xs" class="text-slate-500">{{ $plan->plan_date?->format('d/m/Y') ?? '—' }} &middot; {{ $plan->shrinkage_rate }}% shrinkage</flux:text>
                    </div>
                </div>

                @if($plan->results->isNotEmpty())
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                        @foreach($plan->results as $r)
                            <div class="p-2 bg-slate-50 rounded text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cobertura</p>
                                <p class="text-lg font-bold {{ $r->avg_coverage >= 100 ? 'text-green-600' : ($r->avg_coverage >= 85 ? 'text-amber-600' : 'text-red-600') }}">
                                    {{ number_format($r->avg_coverage, 1) }}%
                                </p>
                            </div>
                            <div class="p-2 bg-slate-50 rounded text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Req. / Disp.</p>
                                <p class="text-lg font-bold text-slate-800">{{ number_format($r->total_staff_required, 1) }} / {{ number_format($r->total_staff_available, 1) }}</p>
                            </div>
                            <div class="p-2 bg-slate-50 rounded text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Max Gap</p>
                                <p class="text-lg font-bold text-red-600">{{ number_format($r->max_gap, 1) }}</p>
                            </div>
                            <div class="p-2 bg-slate-50 rounded text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Gap/Skill</p>
                                <p class="text-lg font-bold text-amber-600">{{ $r->intervals_with_gap }}/{{ $r->intervals_with_skill_gap }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>
        @empty
            <flux:card class="p-8 text-center">
                <flux:icon name="chart-bar" class="size-12 text-slate-300 mx-auto mb-3" />
                <flux:heading>Sin planes de capacidad</flux:heading>
                <flux:text class="text-slate-500">Genere planes en la sección Capacity Planning.</flux:text>
            </flux:card>
        @endforelse
    </div>
</div>
