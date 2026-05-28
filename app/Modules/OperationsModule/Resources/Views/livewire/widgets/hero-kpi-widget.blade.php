<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 xl:grid-cols-6 gap-4">
    @foreach($heroKpis as $kpi)
        <flux:card class="relative overflow-hidden">
            <div class="flex flex-col gap-1">
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ $kpi['label'] }}</span>
                    <flux:icon :name="$kpi['icon']" variant="mini" class="text-zinc-400" />
                </div>

                <div class="flex items-baseline gap-2 mt-1">
                    <span
                        class="text-2xl font-bold tracking-tight @if($kpi['status'] === 'danger') text-red-600 @elseif($kpi['status'] === 'warning') text-amber-600 @elseif($kpi['status'] === 'success') text-green-600 @else text-zinc-900 dark:text-white @endif">
                        {{ $kpi['value'] }}
                    </span>
                </div>

                <div class="flex items-center gap-1 mt-1">
                    <span
                        class="text-xs font-medium {{ str_contains($kpi['delta'], '+') ? 'text-green-600' : (str_contains($kpi['delta'], '-') ? 'text-red-600' : 'text-zinc-400') }}">
                        {{ $kpi['delta'] }}
                    </span>
                    <span class="text-[10px] text-zinc-400">vs ayer</span>
                </div>
            </div>

            <div
                class="absolute bottom-0 left-0 right-0 h-1 @if($kpi['status'] === 'danger') bg-red-500/20 @elseif($kpi['status'] === 'warning') bg-amber-500/20 @elseif($kpi['status'] === 'success') bg-green-500/20 @else bg-zinc-500/10 @endif">
            </div>
        </flux:card>
    @endforeach
</div>
