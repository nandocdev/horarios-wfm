<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm" wire:poll.60s>
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Alertas</h3>
    </div>
    
    <div class="space-y-0 divide-y divide-zinc-100 dark:divide-zinc-700/50">
        @forelse ($alerts as $alert)
            <div class="py-2.5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="shrink-0 flex items-center justify-center">
                        @if ($alert['level'] === 'critical')
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        @elseif ($alert['level'] === 'warning')
                            <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                        @elseif ($alert['level'] === 'info')
                            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        @elseif ($alert['level'] === 'primary')
                            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        @else
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        @endif
                    </div>
                    <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 w-20">{{ $alert['category'] }}</span>
                    <span class="text-xs text-zinc-600 dark:text-zinc-400 truncate">{{ $alert['message'] }}</span>
                </div>
            </div>
        @empty
            <div class="text-center py-6 text-sm text-zinc-400 dark:text-zinc-500">
                Sin alertas activas
            </div>
        @endforelse
    </div>
    <div class="mt-2 pt-2 text-center">
        <a href="{{ route('operations.realtime') ?? '#' }}" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">[Ver todas las alertas...]</a>
    </div>
</div>
