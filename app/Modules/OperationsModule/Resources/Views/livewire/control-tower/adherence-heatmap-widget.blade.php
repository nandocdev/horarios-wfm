<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm"
    wire:poll.300s>
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Actividad por Equipo
        </h3>
    </div>
    <div class="overflow-x-auto pb-2">
        <div class="min-w-max">
            <!-- Header -->
            <div class="flex items-center gap-1 mb-2">
                <div class="w-40 shrink-0"></div>
                @foreach ($hours as $h)
                    <div class="w-8 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                        {{ sprintf('%02d', $h) }}
                    </div>
                @endforeach
            </div>
            <div class="space-y-1">
                @foreach ($rows as $row)
                    <div class="flex items-center gap-1">
                        <div class="w-40 shrink-0 pr-2 leading-tight" title="{{ $row['name'] }}">
                            <div class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate">{{ $row['name'] }}
                            </div>
                            @if ($row['coordinator'])
                                <div class="text-[10px] text-zinc-400 dark:text-zinc-500 truncate">{{ $row['coordinator'] }}
                                </div>
                            @endif
                        </div>
                        @foreach ($row['hours'] as $cell)
                            <div class="w-8 h-8 rounded-sm flex items-center justify-center {{ $cell['class'] }}">
                                @if ($cell['value'] !== null)
                                    <span
                                        class="text-[10px] font-bold text-white">{{ $cell['value'] >= 100 ? '99' : number_format($cell['value'], 0) }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div
        class="flex items-center gap-4 mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-700 text-[10px] text-zinc-500 font-medium">
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-green-500"></span> ≥85%</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-yellow-500"></span> 70-84%</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span> &lt;70%</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-zinc-200 dark:bg-zinc-700"></span>
            S/D</span>
    </div>
</div>