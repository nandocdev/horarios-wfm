<div wire:poll.60s>
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-2.5 sm:gap-4">
        {{-- 1. Agentes Disponibles --}}
        <div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-3 sm:p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between mb-1.5 sm:mb-2">
                <span class="text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Agentes</span>
                <flux:icon name="users" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-zinc-400" />
            </div>
            <div class="text-xl sm:text-3xl font-bold font-mono sm:font-sans text-zinc-900 dark:text-white">{{ $connectedCount }}</div>
            <div class="mt-1.5 sm:mt-2 flex items-center gap-1 text-[10px] sm:text-xs font-medium {{ $agentDelta >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                <flux:icon name="{{ $agentDelta >= 0 ? 'arrow-trending-up' : 'arrow-trending-down' }}" class="w-3 h-3" />
                <span>{{ $agentDelta >= 0 ? '+' : '' }}{{ $agentDelta }} hoy</span>
            </div>
        </div>

        {{-- 2. SLA --}}
        <div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-3 sm:p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between mb-1.5 sm:mb-2">
                <span class="text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">SLA</span>
                <flux:icon name="phone" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-zinc-400" />
            </div>
            <div class="text-xl sm:text-3xl font-bold font-mono sm:font-sans {{ $slaPct >= $slaTarget ? 'text-zinc-900 dark:text-white' : 'text-red-600 dark:text-red-400' }}">
                {{ $slaPct }}%
            </div>
            <div class="mt-1.5 sm:mt-2 text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 font-medium">Meta {{ $slaTarget }}%</div>
        </div>

        {{-- 3. Adherencia --}}
        <div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-3 sm:p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between mb-1.5 sm:mb-2">
                <span class="text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Adherencia</span>
                <flux:icon name="presentation-chart-line" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-zinc-400" />
            </div>
            <div class="text-xl sm:text-3xl font-bold font-mono sm:font-sans {{ $avgAdherence >= $adherenceTarget ? 'text-zinc-900 dark:text-white' : 'text-yellow-600 dark:text-yellow-400' }}">
                {{ $avgAdherence }}%
            </div>
            <div class="mt-1.5 sm:mt-2 text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 font-medium">Meta {{ $adherenceTarget }}%</div>
        </div>

        {{-- 4. Occupancy --}}
        <div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-3 sm:p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between mb-1.5 sm:mb-2">
                <span class="text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Occupancy</span>
                <flux:icon name="bolt" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-zinc-400" />
            </div>
            <div class="text-xl sm:text-3xl font-bold font-mono sm:font-sans text-zinc-900 dark:text-white">{{ $occupancy }}%</div>
            <div class="mt-1.5 sm:mt-2 w-full bg-zinc-100 dark:bg-zinc-700 h-1.5 rounded-full overflow-hidden">
                <div class="h-full {{ $occupancy >= $occupancyTarget ? 'bg-blue-600' : 'bg-red-500' }}" style="width: {{ min($occupancy, 100) }}%"></div>
            </div>
            <div class="mt-1 text-[9px] sm:text-[10px] text-zinc-500 font-medium">Meta {{ $occupancyTarget }}%</div>
        </div>

        {{-- 5. ASA --}}
        <div class="bg-white dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 p-3 sm:p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between mb-1.5 sm:mb-2">
                <span class="text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">ASA</span>
                <flux:icon name="clock" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-zinc-400" />
            </div>
            <div class="text-xl sm:text-3xl font-bold font-mono sm:font-sans {{ $avgQueueTime <= $queueTimeTarget ? 'text-zinc-900 dark:text-white' : 'text-red-600 dark:text-red-400' }}">
                {{ $avgQueueTime }}<span class="text-sm sm:text-lg text-zinc-500 font-normal">s</span>
            </div>
            <div class="mt-1.5 sm:mt-2 text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 font-medium">Meta &lt; {{ $queueTimeTarget }}s</div>
        </div>

        {{-- 6. Cobertura (changes entire bg if deficit) --}}
        <div class="rounded-md border p-3 sm:p-4 shadow-sm flex flex-col justify-between {{ $deficit >= 0 ? 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800/30' }}">
            <div class="flex items-center justify-between mb-1.5 sm:mb-2">
                <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider {{ $deficit >= 0 ? 'text-zinc-500 dark:text-zinc-400' : 'text-red-700 dark:text-red-400' }}">Cobertura</span>
                <flux:icon name="{{ $deficit >= 0 ? 'shield-check' : 'shield-exclamation' }}" class="w-3.5 h-3.5 sm:w-4 sm:h-4 {{ $deficit >= 0 ? 'text-zinc-400' : 'text-red-600 dark:text-red-500' }}" />
            </div>
            <div class="text-xl sm:text-3xl font-bold font-mono sm:font-sans {{ $deficit >= 0 ? 'text-zinc-900 dark:text-white' : 'text-red-700 dark:text-red-400' }}">
                {{ $deficit > 0 ? '+' : '' }}{{ $deficit }}
            </div>
            <div class="mt-1.5 sm:mt-2 text-[10px] sm:text-xs font-medium {{ $deficit >= 0 ? 'text-zinc-500 dark:text-zinc-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $deficit >= 0 ? 'Sin déficit' : 'Déficit de personal' }}
            </div>
        </div>
    </div>
</div>
