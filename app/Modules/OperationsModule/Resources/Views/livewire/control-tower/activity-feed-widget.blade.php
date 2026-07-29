<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm flex flex-col h-full">
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700 shrink-0">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Actividad Reciente</h3>
    </div>
    <div class="flex-1 space-y-0 divide-y divide-zinc-100 dark:divide-zinc-700/50 mb-4 overflow-y-auto max-h-[160px]">
        @forelse ($activities as $activity)
            <div class="py-2 flex items-start gap-3">
                <span class="text-[10px] font-mono text-zinc-400 dark:text-zinc-500 w-10 shrink-0 mt-0.5">{{ $activity['time'] }}</span>
                <span class="text-xs text-zinc-700 dark:text-zinc-300 leading-tight">{{ $activity['text'] }}</span>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-6 h-full">
                <flux:icon name="clock" class="w-6 h-6 mb-2 text-zinc-300 dark:text-zinc-600" />
                <p class="text-xs text-zinc-400 dark:text-zinc-500">Sin actividad reciente</p>
            </div>
        @endforelse
    </div>
    <div class="shrink-0 text-center pt-2 border-t border-zinc-100 dark:border-zinc-700">
        <a href="#" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">[Historial]</a>
    </div>
</div>
