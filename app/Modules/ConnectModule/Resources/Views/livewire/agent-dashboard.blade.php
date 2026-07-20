<div class="space-y-6">
    <x-wfm.page-header title="Mis Datos (Desempeño Individual)" description="Monitorea tus métricas clave de operación en tiempo real.">
        <x-slot:actions>
            <flux:select wire:model.live="dateRange" class="!w-40">
                <flux:select.option value="today">Hoy</flux:select.option>
                <flux:select.option value="this_week">Esta Semana</flux:select.option>
                <flux:select.option value="this_month">Este Mes</flux:select.option>
            </flux:select>
        </x-slot:actions>
    </x-wfm.page-header>

    @if(!$this->employee)
        <div class="rounded-md bg-wfm-danger/10 border border-wfm-danger/20 px-4 py-3 flex items-center gap-3">
            <flux:icon.exclamation-triangle class="w-5 h-5 text-wfm-danger" />
            <p class="text-xs text-wfm-danger font-medium">Tu cuenta no está vinculada a un perfil de empleado válido.</p>
        </div>
    @else
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <x-wfm.kpi :value="$this->metrics['total_calls']" label="Llamadas Atendidas" icon="phone" color="info" comparison="Conectadas a tu extensión" />
            <x-wfm.kpi :value="$this->metrics['avg_talk_time'] . 's'" label="TMO Promedio" icon="clock" color="success" comparison="Tiempo de conversación" />
            <x-wfm.kpi :value="$this->metrics['avg_handle_time'] . 's'" label="AHT (Handle Time)" icon="briefcase" comparison="Conversación + Trabajo" />
            <x-wfm.kpi :value="$this->metrics['abandoned']" label="Llamadas Fallidas" icon="phone-x-mark" color="danger" comparison="Abandonos o rechazos" />
        </div>

        <x-wfm.section title="Registro de Llamadas" description="Detalle de las interacciones recientes.">
            <x-wfm.table :headers="['Hora Inicio', 'Cola (Servicio)', 'Nº Origen', 'T. Conv.', 'T. Trabajo', 'Estado']" compact>
                @forelse($this->recentCalls as $call)
                    <flux:table.row :key="$call->id">
                        <flux:table.cell class="text-xs">{{ $call->ivr_started_at?->format('d/m/Y H:i:s') ?? 'N/A' }}</flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $call->queue?->name ?? 'Desconocida' }}</flux:table.cell>
                        <flux:table.cell class="text-xs font-mono">{{ $call->phone_number ?? 'Oculto' }}</flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $call->talk_time ?? 0 }}s</flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $call->work_time ?? 0 }}s</flux:table.cell>
                        <flux:table.cell>
                            <x-wfm.agent-status :status="match($call->status) { 'closed' => 'available', 'abandoned', 'rejected', 'aborted' => 'busy', default => 'break' }" :label="match($call->status) { 'closed' => 'Atendida', 'abandoned' => 'Abandono', 'rejected' => 'Rechazada', 'aborted' => 'Abortada', default => ucfirst($call->status) }" size="xs" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-wfm.empty icon="phone" message="No hay llamadas en el período seleccionado." />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-wfm.table>
            <div class="mt-4">{{ $this->recentCalls->links() }}</div>
        </x-wfm.section>
    @endif
</div>
