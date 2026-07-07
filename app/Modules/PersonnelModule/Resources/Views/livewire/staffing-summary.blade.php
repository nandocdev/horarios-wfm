<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Inventario de Staffing</flux:heading>
            <flux:subheading>Distribución y demografía de la fuerza laboral activa</flux:subheading>
        </div>
        <flux:button icon="arrow-path" wire:click="loadData">Actualizar</flux:button>
    </div>

    <!-- Resumen General -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card class="flex flex-col items-center justify-center py-4">
            <flux:text class="text-3xl font-black text-slate-900">{{ $stats['total'] }}</flux:text>
            <flux:text size="sm" class="font-medium text-slate-500 uppercase tracking-widest">Total Empleados</flux:text>
        </flux:card>
        <flux:card class="flex flex-col items-center justify-center py-4 border-l-4 border-l-green-500">
            <flux:text class="text-3xl font-black text-green-600">{{ $stats['active'] }}</flux:text>
            <flux:text size="sm" class="font-medium text-slate-500 uppercase tracking-widest">Activos</flux:text>
        </flux:card>
        <flux:card class="flex flex-col items-center justify-center py-4 border-l-4 border-l-red-500">
            <flux:text class="text-3xl font-black text-red-600">{{ $stats['inactive'] }}</flux:text>
            <flux:text size="sm" class="font-medium text-slate-500 uppercase tracking-widest">Inactivos</flux:text>
        </flux:card>
        <flux:card class="flex flex-col items-center justify-center py-4 border-l-4 border-l-amber-500">
            <flux:text class="text-3xl font-black text-amber-600">{{ $stats['managers'] }}</flux:text>
            <flux:text size="sm" class="font-medium text-slate-500 uppercase tracking-widest">Líderes / Managers</flux:text>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Distribución por Equipo -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Personal por Equipo</flux:heading>
            <div class="space-y-3">
                @foreach($byTeam as $team)
                    <div class="flex items-center gap-4">
                        <div class="flex-1">
                            <div class="flex justify-between mb-2">
                                <flux:text size="sm" class="font-bold">{{ $team['name'] }}</flux:text>
                                <flux:text size="xs" class="text-slate-400">{{ $team['employees_count'] }}</flux:text>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2">
                                <div class="bg-slate-600 h-2 rounded-full" style="width: {{ ($team['employees_count'] / max($stats['active'], 1)) * 100 }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        <!-- Distribución por Estatus -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Modalidad de Contratación</flux:heading>
            <flux:table>
                <flux:table.columns class="sticky top-0 z-10 bg-white">
                    <flux:table.column class="sticky top-0 z-10 bg-white">Estatus</flux:table.column>
                    <flux:table.column align="end" class="sticky top-0 z-10 bg-white">Cantidad</flux:table.column>
                    <flux:table.column align="end" class="sticky top-0 z-10 bg-white">%</flux:table.column>
                </flux:table.columns>
                <flux:table.rows class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                    @foreach($byStatus as $status)
                        <flux:table.row class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                            <flux:table.cell class="py-2 font-bold">{{ $status['name'] }}</flux:table.cell>
                            <flux:table.cell align="end" class="py-2">{{ $status['employees_count'] }}</flux:table.cell>
                            <flux:table.cell align="end" class="py-2 text-slate-400">
                                {{ round(($status['employees_count'] / max($stats['active'], 1)) * 100, 1) }}%
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>

    <!-- Distribución por Posición -->
    <flux:card>
        <flux:heading size="lg" class="mb-4">Distribución por Cargos / Posiciones</flux:heading>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($byPosition as $position)
                @if($position['employees_count'] > 0)
                    <div class="p-4 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex justify-between items-center">
                        <div>
                            <flux:text size="xs" class="uppercase tracking-tighter text-slate-400 font-bold">{{ $position['position_code'] }}</flux:text>
                            <flux:text class="font-medium block truncate max-w-[200px]">{{ $position['name'] }}</flux:text>
                        </div>
                        <flux:badge size="sm" color="slate" inset="top" class="font-black">{{ $position['employees_count'] }}</flux:badge>
                    </div>
                @endif
            @endforeach
        </div>
    </flux:card>
</div>
