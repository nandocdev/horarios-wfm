<div wire:poll.60s>
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="users" class="w-4 h-4 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Agentes</span>
            </div>
            <div class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $connectedCount }}</div>
            <div class="mt-1 flex items-center gap-1 text-xs">
                <flux:icon name="arrow-trending-up" class="w-3 h-3 text-green-500" />
                <span class="text-green-600 dark:text-green-400">▲ {{ $agentDelta >= 0 ? '+' : '' }}{{ $agentDelta }} hoy</span>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="phone" class="w-4 h-4 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">SLA</span>
            </div>
            <div class="text-3xl font-bold {{ $slaPct >= $slaTarget ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $slaPct }}%</div>
            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Meta {{ $slaTarget }}%</div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="clock" class="w-4 h-4 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Adherencia</span>
            </div>
            <div class="text-3xl font-bold {{ $avgAdherence >= $adherenceTarget ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $avgAdherence }}%</div>
            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Meta {{ $adherenceTarget }}%</div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="chart-bar" class="w-4 h-4 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Occupancy</span>
            </div>
            <div class="text-3xl font-bold {{ $occupancy >= $occupancyTarget ? ($occupancy <= 92 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400') : 'text-red-600 dark:text-red-400' }}">{{ $occupancy }}%</div>
            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Meta {{ $occupancyTarget }}%</div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="clock" class="w-4 h-4 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">ASA</span>
            </div>
            <div class="text-3xl font-bold {{ $avgQueueTime <= $queueTimeTarget ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $avgQueueTime }}<span class="text-lg">s</span></div>
            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Meta &lt;{{ $queueTimeTarget }}s</div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="{{ $deficit >= 0 ? 'check-circle' : 'exclamation-triangle' }}" class="w-4 h-4 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Cobertura</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold {{ $deficit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $deficit >= 0 ? '+' : '' }}{{ $deficit }}</span>
                <span class="text-sm text-zinc-500">{{ $deficit >= 0 ? 'sin déficit' : 'déficit' }}</span>
            </div>
        </div>
    </div>
</div>
