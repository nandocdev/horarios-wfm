<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5" wire:poll.120s>
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Notificaciones</h3>
    <div class="space-y-3">
        @forelse ($notifications as $n)
            <div class="flex items-start gap-3 text-xs">
                <flux:icon name="{{ $n['icon'] }}" class="w-4 h-4 mt-0.5 text-zinc-500 dark:text-zinc-400 shrink-0" />
                <div class="min-w-0">
                    <p class="text-zinc-700 dark:text-zinc-300 truncate">{{ $n['text'] }}</p>
                    <span class="text-zinc-400 dark:text-zinc-500">{{ $n['time'] }}</span>
                </div>
            </div>
        @empty
            <div class="text-center py-6 text-sm text-zinc-400 dark:text-zinc-500">
                Sin notificaciones
            </div>
        @endforelse
    </div>
    <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-700">
        <a href="#" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Centro de Notificaciones →</a>
    </div>
</div>
