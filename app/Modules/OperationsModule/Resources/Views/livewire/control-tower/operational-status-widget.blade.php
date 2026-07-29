<div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm" wire:poll.30s>
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">Estado Operacional</h3>
        <span class="text-xs font-medium text-zinc-500">Total: {{ $total }} agentes</span>
    </div>
    
    <div class="space-y-3">
        @foreach ($statuses as $status)
            <div class="flex items-center gap-4">
                <span class="w-20 text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $status['label'] }}</span>
                <div class="flex-1 flex items-center">
                    <div class="bg-zinc-100 dark:bg-zinc-700 h-6 flex items-center justify-end px-2 transition-all duration-500 {{ $status['color'] }} rounded-sm"
                         style="width: {{ max(2, $status['pct']) }}%">
                    </div>
                    <span class="ml-2 text-xs font-bold text-zinc-800 dark:text-zinc-200 w-8">{{ $status['count'] }}</span>
                </div>
                <span class="w-12 text-right text-xs text-zinc-500 dark:text-zinc-400">{{ $status['pct'] }}%</span>
            </div>
        @endforeach
    </div>
</div>
