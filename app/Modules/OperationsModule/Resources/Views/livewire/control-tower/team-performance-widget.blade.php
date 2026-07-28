<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5" wire:poll.120s>
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Equipos</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($teams as $team)
            <a href="{{ route('operations.team-performance-summary', ['team' => $team['id']]) }}"
               class="block p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors"
               wire:navigate>
                <p class="text-xs font-medium text-zinc-800 dark:text-zinc-200 truncate">{{ $team['name'] }}</p>
                <div class="mt-1 flex items-baseline gap-1">
                    <span class="text-lg font-bold {{ $team['occupancyClass'] }}">{{ $team['occupancy'] }}%</span>
                    <span class="text-[10px] text-zinc-400">occ</span>
                </div>
                <div class="mt-1 flex gap-2 text-[10px] text-zinc-400">
                    <span>{{ $team['adherence'] }}% adh</span>
                    <span>{{ $team['calls'] }} llam</span>
                </div>
                <div class="mt-1 text-[10px] text-zinc-400">{{ $team['members'] }} miembros</div>
            </a>
        @endforeach
    </div>
</div>
