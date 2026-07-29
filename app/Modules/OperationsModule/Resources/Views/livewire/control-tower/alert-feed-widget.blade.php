<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm" wire:poll.60s>
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Alertas</h3>
    </div>
    
    <div class="max-h-[280px] overflow-y-auto space-y-1">
        @forelse ($alerts as $alert)
            <div class="flex items-start gap-3 p-2.5 rounded-md {{ $alert['level'] === 'critical' ? 'bg-red-50 dark:bg-red-950/20' : ($alert['level'] === 'warning' ? 'bg-orange-50 dark:bg-orange-950/20' : 'bg-zinc-50 dark:bg-zinc-800/50') }}">
                <div class="shrink-0 mt-0.5">
                    @if ($alert['level'] === 'critical')
                        <div class="w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-red-500/20"></div>
                    @elseif ($alert['level'] === 'warning')
                        <div class="w-2.5 h-2.5 rounded-full bg-orange-500 ring-2 ring-orange-500/20"></div>
                    @elseif ($alert['level'] === 'info')
                        <div class="w-2.5 h-2.5 rounded-full bg-yellow-500 ring-2 ring-yellow-500/20"></div>
                    @else
                        <div class="w-2.5 h-2.5 rounded-full bg-green-500 ring-2 ring-green-500/20"></div>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ $alert['category'] }}</span>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed mt-0.5">{{ $alert['message'] }}</p>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <flux:icon name="check-circle" class="w-8 h-8 mb-2 text-green-400" />
                <p class="text-sm text-zinc-400 dark:text-zinc-500">Sin alertas activas</p>
            </div>
        @endforelse
    </div>
    <div class="mt-2 pt-2 text-center">
        <a href="{{ route('operations.realtime') ?? '#' }}" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">[Ver todas las alertas...]</a>
    </div>
</div>
