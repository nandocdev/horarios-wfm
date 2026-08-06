<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm" wire:poll.120s>
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Equipos</h3>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        @forelse ($teams as $team)
            <a href="{{ Route::has('operations.team-performance-summary') ? route('operations.team-performance-summary', ['team' => $team['id']]) : '#' }}"
               class="block p-3 rounded-md border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-700/30 hover:border-blue-300 dark:hover:border-blue-700 transition-colors"
               wire:navigate>
                <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 truncate mb-2">{{ $team['name'] }}</p>
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-zinc-500 font-medium">Occupancy</span>
                        <span class="text-xs font-bold {{ $team['occupancyClass'] }}">{{ $team['occupancy'] }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-zinc-500 font-medium">Adherencia</span>
                        <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">{{ $team['adherence'] }}%</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-2 lg:col-span-3 rounded-md border border-dashed border-zinc-200 dark:border-zinc-700 p-6 text-center">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                    Sin métricas para la fecha seleccionada.
                </p>
                <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1">
                    Las métricas de intervalo se generan cada 15 minutos cuando hay un horario publicado.
                </p>
            </div>
        @endforelse
    </div>
</div>
