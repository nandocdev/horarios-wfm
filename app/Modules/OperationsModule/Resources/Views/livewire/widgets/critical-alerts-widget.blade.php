<flux:card>
    <flux:heading size="lg" class="mb-4">Alertas Críticas</flux:heading>
    <div class="space-y-3">
        {{-- 1. Alerta de Aprobaciones Pendientes --}}
        @if($pendingApprovals > 0)
            <div class="p-3 bg-blue-50 dark:bg-blue-950/20 border-l-4 border-blue-500 flex items-start gap-3">
                <flux:icon name="clock" class="text-blue-500 mt-0.5" />
                <div>
                    <flux:text size="sm" class="font-bold text-blue-900 dark:text-blue-200">
                        Pendientes de Aprobación
                    </flux:text>
                    <flux:text size="sm" class="text-blue-700 dark:text-blue-300">
                        Hay {{ $pendingApprovals }} solicitudes esperando revisión de WFM.
                    </flux:text>
                </div>
            </div>
        @endif

        {{-- 2. Alertas de Colisiones Horarias (Pre-check Preventivo) --}}
        @foreach($scheduleConflicts as $conflict)
            <div class="p-3 bg-amber-50 dark:bg-amber-950/20 border-l-4 border-amber-500 flex items-start gap-3">
                <flux:icon name="shield-exclamation" variant="solid" class="text-amber-500 mt-0.5 w-5 h-5" />
                <div>
                    <flux:text size="sm" class="font-bold text-amber-900 dark:text-amber-200">
                        Inconsistencia Horaria
                    </flux:text>
                    <flux:text size="sm" class="text-amber-700 dark:text-amber-300 leading-tight">
                        {{ $conflict['message'] }}
                    </flux:text>
                </div>
            </div>
        @endforeach

        {{-- 3. Alertas de SLA Crítico de Colas CTI UCCX --}}
        @foreach($criticalQueues as $queue)
            <div class="p-3 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 flex items-start gap-3">
                <flux:icon name="exclamation-triangle" class="text-red-500 mt-0.5" />
                <div>
                    <flux:text size="sm" class="font-bold text-red-900 dark:text-red-200">
                        SLA Crítico: {{ $queue['name'] }}
                    </flux:text>
                    <flux:text size="sm" class="text-red-700 dark:text-red-300">
                        Llamadas en espera: {{ $queue['waiting'] }}. Nivel de servicio al {{ round($queue['sl'], 0) }}%.
                    </flux:text>
                </div>
            </div>
        @endforeach

        {{-- 4. Estado Sin Alertas --}}
        @if($pendingApprovals === 0 && empty($scheduleConflicts) && empty($criticalQueues))
             <div class="flex flex-col items-center justify-center py-8 text-center">
                <flux:icon name="check-circle" class="w-10 h-10 text-green-500 mb-2" />
                <flux:text size="sm" class="text-zinc-500">No hay alertas críticas en este momento.</flux:text>
            </div>
        @endif
    </div>
</flux:card>
