<div class="p-8 space-y-8 bg-slate-50 min-h-screen">
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Mi Jornada</flux:heading>
            <flux:subheading>Monitoreo de actividades y estados en tiempo real para {{ $targetEmployee->full_name }}</flux:subheading>
        </div>

        @if($isManager)
            <div class="flex items-center gap-4 bg-white dark:bg-zinc-800 p-2 rounded-md border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <flux:field>
                    <flux:select wire:model.live="targetTeamId" placeholder="Filtrar por equipo..." class="min-w-[200px]">
                        <flux:select.option value="">Todos los equipos</flux:select.option>
                        @foreach($availableTeams as $team)
                            <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:select wire:model.live="targetEmployeeId" placeholder="Seleccionar colaborador..." class="min-w-[250px]">
                        @foreach($availableEmployees as $emp)
                            <flux:select.option value="{{ $emp->id }}">{{ $emp->full_name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
        @endif
    </header>

    {{-- Resumen de Métricas del Día --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:card class="p-4 flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-md">
                <flux:icon name="clock" variant="mini" />
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500 font-medium">Tiempo Total</flux:text>
                <flux:heading size="lg">{{ $stats['total_time'] ?? '00h 00m' }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="p-4 flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-md">
                <flux:icon name="bolt" variant="mini" />
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500 font-medium">Tiempo Productivo</flux:text>
                <flux:heading size="lg">{{ $stats['productive_time'] ?? '00h 00m' }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="p-4 flex items-center gap-4">
            <div class="p-3 bg-slate-100 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 rounded-md">
                <flux:icon name="chart-pie" variant="mini" />
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500 font-medium">Ocupación Promedio</flux:text>
                <flux:heading size="lg">{{ $stats['occupancy'] ?? 0 }}%</flux:heading>
            </div>
        </flux:card>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {{-- Columna Izquierda: Información en Tiempo Real --}}
        <div class="lg:col-span-4 space-y-6">
            <flux:card class="p-6">
                <flux:heading class="mb-6">Estado Actual</flux:heading>
                @livewire('operations.agent-realtime-card', [
                    'employeeId' => $targetEmployee->id,
                    'ciscoUsername' => $targetEmployee->cisco_username
                ], key('realtime-'.$targetEmployee->id))
            </flux:card>
        </div>
        
        {{-- Columna Derecha: Timeline --}}
        <div class="lg:col-span-8">
            <flux:card class="p-6 h-[800px] flex flex-col">
                <div class="flex items-center justify-between mb-6">
                    <flux:heading>Línea de Tiempo</flux:heading>
                    <div class="flex items-center gap-2">
                         <flux:badge color="slate" variant="pill">Hoy</flux:badge>
                         <flux:button icon="arrow-path" size="xs" variant="subtle" wire:click="loadStats" />
                    </div>
                </div>
                
                <div class="flex-1 min-h-0 overflow-hidden">
                    @livewire('operations.agent-timeline', ['employeeId' => $targetEmployee->id], key('timeline-'.$targetEmployee->id))
                </div>
            </flux:card>
        </div>
    </div>
</div>
