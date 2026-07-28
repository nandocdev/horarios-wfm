<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5" wire:poll.60s>
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Alertas</h3>
    <div class="space-y-3">
        @forelse ($alerts as $alert)
            <div class="flex items-start gap-3 p-2.5 rounded-lg {{ $alert['level'] === 'critical' ? 'bg-red-50 dark:bg-red-900/20' : ($alert['level'] === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/20' : ($alert['level'] === 'info' ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-zinc-50 dark:bg-zinc-700/50')) }}">
                <div class="mt-0.5">
                    @if ($alert['level'] === 'critical')
                        <flux:icon name="exclamation-triangle" class="w-4 h-4 text-red-500" />
                    @elseif ($alert['level'] === 'warning')
                        <flux:icon name="exclamation-circle" class="w-4 h-4 text-yellow-500" />
                    @elseif ($alert['level'] === 'info')
                        <flux:icon name="information-circle" class="w-4 h-4 text-blue-500" />
                    @else
                        <flux:icon name="check-circle" class="w-4 h-4 text-green-500" />
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-zinc-800 dark:text-zinc-200">{{ $alert['category'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $alert['message'] }}</p>
                </div>
            </div>
        @empty
            <div class="text-center py-6 text-sm text-zinc-400 dark:text-zinc-500">
                Sin alertas activas
            </div>
        @endforelse
    </div>
    <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-700">
        <a href="{{ route('operations.realtime') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Ver todas las alertas →</a>
    </div>
</div>
