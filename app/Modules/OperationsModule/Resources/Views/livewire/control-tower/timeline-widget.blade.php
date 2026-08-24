<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm flex flex-col h-full">
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700 shrink-0">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Timeline del día</h3>
    </div>
    <div class="flex-1 overflow-y-auto max-h-[160px] pr-2">
        <div class="relative border-l-2 border-zinc-200 dark:border-zinc-700 ml-3 space-y-4">
            @forelse ($events as $event)
                <div class="relative pl-4">
                    <div class="absolute -left-[5px] top-1.5 w-2 h-2 rounded-full ring-2 ring-white dark:ring-zinc-800
                        {{ $event['type'] === 'success' ? 'bg-green-500' : ($event['type'] === 'warning' ? 'bg-yellow-500' : ($event['type'] === 'info' ? 'bg-zinc-400' : ($event['type'] === 'critical' ? 'bg-red-500' : 'bg-blue-500'))) }}">
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-zinc-500">{{ $event['time'] }}</span>
                        <span class="text-xs font-medium text-zinc-800 dark:text-zinc-200">{{ $event['title'] }}</span>
                    </div>
                </div>
            @empty
                <div class="pl-4 py-4">
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">Sin eventos relevantes</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
