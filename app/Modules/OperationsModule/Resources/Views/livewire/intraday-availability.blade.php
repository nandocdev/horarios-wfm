<div wire:poll.{{ $refreshInterval }}s class="space-y-8">
    <div class="flex items-center justify-between">
        <flux:heading size="xl" level="1">Operación en Tiempo Real</flux:heading>
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <span class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-sm bg-green-600 opacity-75"></span>
              <span class="relative inline-flex rounded-sm h-3 w-3 bg-green-600"></span>
            </span>
            Actualizando cada {{ $refreshInterval }}s
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Adherencia -->
        <flux:card>
            <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-slate-500">Adherencia Real Intradía</span>
                <div class="flex items-end gap-2">
                    <span class="text-3xl font-bold {{ $this->realtimeMetrics['adherence'] < 85 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $this->realtimeMetrics['adherence'] }}%
                    </span>
                </div>
            </div>
        </flux:card>

        <!-- Agentes Conectados -->
        <flux:card>
            <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-slate-500">Agentes Conectados / Agendados</span>
                <div class="flex items-end gap-2">
                    <span class="text-3xl font-bold text-slate-900 dark:text-white">
                        {{ $this->realtimeMetrics['total_connected'] }}
                    </span>
                    <span class="text-lg text-slate-500 mb-1">
                        / {{ $this->realtimeMetrics['total_scheduled'] }}
                    </span>
                </div>
                <div class="flex items-center gap-2 mt-1 text-xs">
                    <span class="text-red-600 font-medium">{{ $this->realtimeMetrics['total_absent'] }} Ausentes</span>
                    <span class="text-gray-400">&bull;</span>
                    <span class="text-zinc-500">{{ $this->realtimeMetrics['total_exceptions'] }} Excepcionados</span>
                </div>
            </div>
        </flux:card>

        <!-- En Llamada (TALKING) -->
        <flux:card>
            <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-slate-500">En Llamada (TALKING)</span>
                <div class="flex items-end gap-2">
                    <span class="text-3xl font-bold text-blue-600">
                        {{ $this->realtimeMetrics['talking'] }}
                    </span>
                </div>
            </div>
        </flux:card>

        <!-- Disponibles (READY) -->
        <flux:card>
            <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-slate-500">Disponibles (READY)</span>
                <div class="flex items-end gap-2">
                    <span class="text-3xl font-bold text-green-600">
                        {{ $this->realtimeMetrics['ready'] }}
                    </span>
                </div>
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Breakdown de Pausas -->
        <div class="lg:col-span-1">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Estados Auxiliares (NOT READY)</flux:heading>
                
                @if(empty($this->realtimeMetrics['not_ready_breakdown']))
                    <div class="text-slate-500 text-sm py-4 text-center">No hay agentes en pausa.</div>
                @else
                    <div class="space-y-3">
                        @foreach($this->realtimeMetrics['not_ready_breakdown'] as $reason => $count)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ $reason ?: 'Sin Razón' }}</span>
                                <flux:badge variant="subtle" color="slate" class="rounded-md">{{ $count }}</flux:badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>
        </div>

        <!-- Tabla detallada (CSQs en atención) -->
        <div class="lg:col-span-2">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Colas en Atención (CSQs)</flux:heading>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-500 uppercase bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors duration-150">
                                <th class="px-4 py-2 min-w-[200px]">Nombre de la Cola (CSQ)</th>
                                <th class="px-4 py-2 text-center">Espera</th>
                                <th class="px-4 py-2 text-center">LWT</th>
                                <th class="px-4 py-2 text-center">SL %</th>
                                <th class="px-4 py-2 text-center">TALKING</th>
                                <th class="px-4 py-2 text-center">OFREC</th>
                                <th class="px-4 py-2 text-center">CONT</th>
                                <th class="px-4 py-2 text-center text-red-600">ABAND</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse($this->realtimeMetrics['csq_summary'] as $csq)
                                @php
                                    $isCritical = $csq->calls_waiting > 5;
                                    $isWarning = $csq->calls_waiting > 2 && $csq->calls_waiting <= 5;
                                    $sl = $csq->service_level_short_term;
                                @endphp
                                <tr class="{{ $isCritical ? 'bg-red-50/50 hover:bg-red-100/50 transition-colors duration-150 dark:bg-red-900/10' : '' }}">
                                    <td class="px-4 py-2 font-medium text-slate-900 dark:text-white">
                                        {{ $csq->csq_name }}
                                    </td>
                                    <td class="px-4 py-2 text-center font-bold">
                                        <span class="{{ $isCritical ? 'text-red-600' : ($isWarning ? 'text-amber-500' : 'text-slate-900 dark:text-zinc-300') }}">
                                            {{ $csq->calls_waiting }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-center font-mono text-xs">
                                        {{ sprintf('%02d:%02d', floor($csq->longest_call_in_queue / 60), $csq->longest_call_in_queue % 60) }}
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <flux:badge :color="$sl < 80 ? 'red' : ($sl < 90 ? 'amber' : 'green')" variant="subtle" size="sm" class="rounded-md">
                                            {{ number_format($sl, 0) }}%
                                        </flux:badge>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <span class="text-blue-600 font-semibold">{{ $csq->agents_talking }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-center text-slate-600 font-semibold">
                                        {{ $csq->total_calls_since_midnight }}
                                    </td>
                                    <td class="px-4 py-2 text-center text-green-600 font-semibold">
                                        {{ $csq->calls_handled_since_midnight }}
                                    </td>
                                    <td class="px-4 py-2 text-center text-red-600 font-semibold">
                                        {{ $csq->calls_abandoned_since_midnight }}
                                    </td>
                                </tr>
                            @empty
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors duration-150">
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                        No hay datos de colas disponibles. Ejecuta el comando de sincronización.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>
    </div>
</div>
