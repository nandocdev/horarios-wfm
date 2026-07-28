<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5" wire:poll.30s>
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Estado Operacional</h3>
    <div class="space-y-3">
        @foreach ($statuses as $status)
            <div class="flex items-center gap-3">
                <span class="w-24 text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ $status['label'] }}</span>
                <div class="flex-1 bg-zinc-100 dark:bg-zinc-700 rounded-full h-5 overflow-hidden">
                    <div class="h-5 rounded-full {{ $status['color'] }} flex items-center justify-end px-2 transition-all duration-500"
                         style="width: {{ max(3, $status['pct']) }}%">
                        @if ($status['pct'] > 10)
                            <span class="text-[10px] font-bold text-white">{{ $status['count'] }}</span>
                        @endif
                    </div>
                </div>
                <span class="w-10 text-right text-xs text-zinc-500 dark:text-zinc-400">{{ $status['pct'] }}%</span>
            </div>
        @endforeach
    </div>
    <div class="mt-3 text-xs text-zinc-400">Total: {{ $total }} agentes</div>
</div>
