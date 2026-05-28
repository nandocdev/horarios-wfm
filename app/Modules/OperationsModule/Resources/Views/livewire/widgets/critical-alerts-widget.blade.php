<flux:card>
    <flux:heading size="lg" class="mb-4">Alertas Críticas</flux:heading>
    <div class="space-y-3">
        @if($pendingApprovals > 0)
            <div class="p-3 bg-blue-50 dark:bg-blue-950/20 border-l-4 border-blue-500 flex items-start gap-3">
                <flux:icon name="clock" class="text-blue-500 mt-0.5" />
                <div>
                    <flux:text size="sm" class="font-bold text-blue-900 dark:text-blue-200">Pendientes de
                        Aprobación</flux:text>
                    <flux:text size="sm" class="text-blue-700 dark:text-blue-300">Hay
                        {{ $pendingApprovals }} solicitudes esperando revisión de WFM.
                    </flux:text>
                </div>
            </div>
        @endif

        @foreach($queueStats as $queue)
            @if($queue['status'] === 'danger')
                <div class="p-3 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 flex items-start gap-3">
                    <flux:icon name="exclamation-triangle" class="text-red-500 mt-0.5" />
                    <div>
                        <flux:text size="sm" class="font-bold text-red-900 dark:text-red-200">SLA Crítico:
                            {{ $queue['name'] }}
                        </flux:text>
                        <flux:text size="sm" class="text-red-700 dark:text-blue-300">Llamadas en espera:
                            {{ $queue['waiting'] }}. Nivel de servicio al {{ $queue['sl'] }}%.
                        </flux:text>
                    </div>
                </div>
            @endif
        @endforeach

        @if($pendingApprovals === 0 && collect($queueStats)->every(fn($q) => $q['status'] !== 'danger'))
             <div class="flex flex-col items-center justify-center py-10 text-center">
                <flux:icon name="check-circle" class="w-10 h-10 text-green-500 mb-2" />
                <flux:text size="sm" class="text-zinc-500">No hay alertas críticas en este momento.</flux:text>
            </div>
        @endif
    </div>
</flux:card>
