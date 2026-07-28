<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Actividad Reciente</h3>
    <div class="space-y-2">
        @forelse ($activities as $activity)
            <div class="flex items-start gap-2 text-xs">
                <span class="text-zinc-400 dark:text-zinc-500 w-10 shrink-0">{{ $activity['time'] }}</span>
                <span class="text-zinc-700 dark:text-zinc-300">{{ $activity['text'] }}</span>
            </div>
        @empty
            <p class="text-xs text-zinc-400 dark:text-zinc-500 text-center py-3">Sin actividad reciente</p>
        @endforelse
    </div>
</div>
