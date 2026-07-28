<div class="mt-6" wire:poll.60s>
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
        {{-- 1. Agentes Disponibles --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="users" class="w-4 h-4 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Disponibles</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold {{ $availableAgents >= $requiredMinimum ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $availableAgents }}
                </span>
                <span class="text-sm text-zinc-400">/ {{ $scheduledToday }} prog.</span>
            </div>
            <div class="mt-1 flex items-center gap-1 text-xs">
                @if ($availableAgents >= $requiredMinimum)
                    <flux:icon name="arrow-trending-up" class="w-3 h-3 text-green-500" />
                    <span class="text-green-600 dark:text-green-400">+{{ $availableAgents - $requiredMinimum }} sobre mínimo</span>
                @else
                    <flux:icon name="arrow-trending-down" class="w-3 h-3 text-red-500" />
                    <span class="text-red-600 dark:text-red-400">-{{ $requiredMinimum - $availableAgents }} bajo mínimo</span>
                @endif
            </div>
        </div>

        {{-- 2. Ocupación --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="chart-bar" class="w-4 h-4 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Ocupación</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold {{ $occupancy > 92 ? 'text-yellow-600 dark:text-yellow-400' : ($occupancy >= $occupancyTarget ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400') }}">
                    {{ $occupancy }}%
                </span>
                @if ($occupancyDelta !== null)
                    <span class="text-xs {{ $occupancyDelta >= 0 ? 'text-green-500' : 'text-red-500' }}">
                        {{ $occupancyDelta >= 0 ? '+' : '' }}{{ number_format($occupancyDelta, 1) }}%
                    </span>
                @endif
            </div>
            <div class="mt-2 w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                <div class="h-1.5 rounded-full {{ $occupancy >= $occupancyTarget ? 'bg-green-500' : 'bg-red-500' }}"
                     style="width: {{ min(100, $occupancy) }}%"></div>
            </div>
            <div class="mt-1 text-xs text-zinc-400">Meta {{ $occupancyTarget }}%</div>
        </div>

        {{-- 3. Adherencia --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="clock" class="w-4 h-4 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Adherencia</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold {{ $avgAdherence >= $adherenceTarget ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $avgAdherence }}%
                </span>
            </div>
            <div class="mt-2 w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                <div class="h-1.5 rounded-full {{ $avgAdherence >= $adherenceTarget ? 'bg-green-500' : 'bg-red-500' }}"
                     style="width: {{ min(100, $avgAdherence) }}%"></div>
            </div>
            <div class="mt-1 text-xs text-zinc-400">Objetivo {{ $adherenceTarget }}%</div>
        </div>

        {{-- 4. SLA --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="phone" class="w-4 h-4 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Service Level</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold {{ $slaPct >= $slaTarget ? ($slaPct >= $slaTarget + 5 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400') : 'text-red-600 dark:text-red-400' }}">
                    {{ $slaPct }}%
                </span>
            </div>
            <div class="mt-2 w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                <div class="h-1.5 rounded-full {{ $slaPct >= $slaTarget ? 'bg-green-500' : 'bg-red-500' }}"
                     style="width: {{ min(100, $slaPct) }}%"></div>
            </div>
            <div class="mt-1 text-xs text-zinc-400">Meta {{ $slaTarget }}%</div>
        </div>

        {{-- 5. Llamadas Esperando --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="queue-list" class="w-4 h-4 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Esperando</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold {{ $waiting > 5 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
                    {{ $waiting }}
                </span>
                @if ($avgQueueTime > 0)
                    <span class="text-sm text-zinc-400">ASA {{ $avgQueueTime }}s</span>
                @endif
            </div>
            <div class="mt-1 text-xs text-zinc-400">{{ $totalHandled ?? 0 }} atendidas hoy</div>
        </div>

        {{-- 6. Cobertura --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="chart-bar-square" class="w-4 h-4 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Cobertura</span>
            </div>
            <div class="flex items-baseline gap-2">
                @if ($coverageStatus === 'surplus')
                    <flux:icon name="arrow-up-circle" class="w-5 h-5 text-green-500" />
                    <span class="text-3xl font-bold text-green-600 dark:text-green-400">+{{ $deficit }}</span>
                    <span class="text-sm text-zinc-400">Agentes</span>
                @else
                    <flux:icon name="arrow-down-circle" class="w-5 h-5 text-red-500" />
                    <span class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $deficit }}</span>
                    <span class="text-sm text-zinc-400">Déficit</span>
                @endif
            </div>
            <div class="mt-1 text-xs text-zinc-400">
                {{ $exceptionsToday }} excepciones hoy
            </div>
        </div>
    </div>
</div>
