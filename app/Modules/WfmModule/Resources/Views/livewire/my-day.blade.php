<div class="p-8 space-y-8 bg-slate-50 min-h-screen">
    <header>
        <flux:heading size="xl" level="1">Mi Jornada</flux:heading>
        <flux:subheading>Monitoreo de actividades y estados en tiempo real para {{ $employee->full_name }}</flux:subheading>
    </header>
    
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {{-- Columna Izquierda: Información en Tiempo Real --}}
        <div class="lg:col-span-4 space-y-6">
            <flux:card class="p-6">
                <flux:heading class="mb-6">Estado Actual</flux:heading>
                @livewire('operations.agent-realtime-card', [
                    'employeeId' => $employee->id,
                    'ciscoUsername' => $employee->cisco_username
                ])
            </flux:card>
        </div>
        
        {{-- Columna Derecha: Timeline --}}
        <div class="lg:col-span-8">
            <flux:card class="p-6 h-[800px] flex flex-col">
                <div class="flex items-center justify-between mb-6">
                    <flux:heading>Línea de Tiempo</flux:heading>
                    <flux:badge color="slate" variant="pill">Hoy</flux:badge>
                </div>
                
                <div class="flex-1 min-h-0 overflow-hidden">
                    @livewire('operations.agent-timeline', ['employeeId' => $employee->id])
                </div>
            </flux:card>
        </div>
    </div>
</div>

