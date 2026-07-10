<div class="space-y-8 max-w-4xl mx-auto flex-1 flex flex-col">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Resumen de Solicitudes WFM</flux:heading>
            <flux:subheading>Monitoreo de flujos de aprobación y ausentismo programado</flux:subheading>
        </div>
        <flux:button icon="arrow-path" wire:click="loadData">Actualizar</flux:button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Tarjeta de Permisos -->
        <flux:card class="p-0 overflow-hidden">
            <div class="p-4 bg-slate-50/50 dark:bg-slate-900/10 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-md">
                        <flux:icon name="document-text" class="w-5 h-5 text-slate-600 dark:text-slate-300" />
                    </div>
                    <flux:heading size="lg">Solicitudes de Permiso</flux:heading>
                </div>
            </div>
            <div class="p-4 grid grid-cols-2 gap-4">
                <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-md border border-slate-100 dark:border-slate-700">
                    <flux:text size="xs" class="uppercase font-bold text-slate-400">Total</flux:text>
                    <flux:text size="xl" class="font-black">{{ $leaveStats['total'] }}</flux:text>
                </div>
                <div class="p-4 bg-amber-50 dark:bg-amber-900/10 rounded-md border border-amber-100 dark:border-amber-900/30">
                    <flux:text size="xs" class="uppercase font-bold text-amber-600">Pendientes</flux:text>
                    <flux:text size="xl" class="font-black text-amber-600">{{ $leaveStats['pending'] }}</flux:text>
                </div>
                <div class="p-4 bg-green-50 dark:bg-green-900/10 rounded-md border border-green-100 dark:border-green-900/30">
                    <flux:text size="xs" class="uppercase font-bold text-green-600">Aprobadas</flux:text>
                    <flux:text size="xl" class="font-black text-green-600">{{ $leaveStats['approved'] }}</flux:text>
                </div>
                <div class="p-4 bg-red-50 dark:bg-red-900/10 rounded-md border border-red-100 dark:border-red-900/30">
                    <flux:text size="xs" class="uppercase font-bold text-red-600">Rechazadas</flux:text>
                    <flux:text size="xl" class="font-black text-red-600">{{ $leaveStats['rejected'] }}</flux:text>
                </div>
            </div>
        </flux:card>

        <!-- Tarjeta de Cambios de Turno -->
        <flux:card class="p-0 overflow-hidden">
            <div class="p-4 bg-slate-50/50 dark:bg-slate-900/10 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-md">
                        <flux:icon name="arrows-right-left" class="w-5 h-5 text-slate-600 dark:text-slate-300" />
                    </div>
                    <flux:heading size="lg">Cambios de Turno</flux:heading>
                </div>
            </div>
            <div class="p-4 grid grid-cols-2 gap-4">
                <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-md border border-slate-100 dark:border-slate-700">
                    <flux:text size="xs" class="uppercase font-bold text-slate-400">Total</flux:text>
                    <flux:text size="xl" class="font-black">{{ $swapStats['total'] }}</flux:text>
                </div>
                <div class="p-4 bg-amber-50 dark:bg-amber-900/10 rounded-md border border-amber-100 dark:border-amber-900/30">
                    <flux:text size="xs" class="uppercase font-bold text-amber-600">Pendientes</flux:text>
                    <flux:text size="xl" class="font-black text-amber-600">{{ $swapStats['pending'] }}</flux:text>
                </div>
                <div class="p-4 bg-green-50 dark:bg-green-900/10 rounded-md border border-green-100 dark:border-green-900/30">
                    <flux:text size="xs" class="uppercase font-bold text-green-600">Aprobadas</flux:text>
                    <flux:text size="xl" class="font-black text-green-600">{{ $swapStats['approved'] }}</flux:text>
                </div>
                <div class="p-4 bg-red-50 dark:bg-red-900/10 rounded-md border border-red-100 dark:border-red-900/30">
                    <flux:text size="xs" class="uppercase font-bold text-red-600">Rechazadas</flux:text>
                    <flux:text size="xl" class="font-black text-red-600">{{ $swapStats['rejected'] }}</flux:text>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Distribución por Tipo de Permiso -->
    <flux:card>
        <flux:heading size="lg" class="mb-4">Motivos de Permiso más frecuentes</flux:heading>
        <div class="space-y-4">
            @foreach($byType as $type)
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <div class="flex justify-between mb-1">
                            <flux:text size="sm" class="font-bold uppercase tracking-tight">{{ $type['type'] ?: 'Otro' }}</flux:text>
                            <flux:text size="xs" class="text-slate-400">{{ $type['count'] }}</flux:text>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2">
                            <div class="bg-slate-600 h-2 rounded-full" style="width: {{ ($type['count'] / max($leaveStats['total'], 1)) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </flux:card>
</div>
