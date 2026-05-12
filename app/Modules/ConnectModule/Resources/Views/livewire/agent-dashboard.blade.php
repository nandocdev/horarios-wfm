<div class="space-y-6">
    <!-- Header y Controles -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <flux:heading size="xl">Mis Datos (Desempeño Individual)</flux:heading>
            <flux:subheading>Monitorea tus métricas clave de operación en tiempo real</flux:subheading>
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="dateRange">
                <flux:select.option value="today">Hoy</flux:select.option>
                <flux:select.option value="this_week">Esta Semana</flux:select.option>
                <flux:select.option value="this_month">Este Mes</flux:select.option>
            </flux:select>
        </div>
    </div>

    <!-- Alerta si no hay empleado vinculado -->
    @if(!$this->employee)
        <flux:card class="bg-red-50 border-red-200">
            <div class="flex items-center gap-3 text-red-800">
                <flux:icon.exclamation-triangle class="w-6 h-6" />
                <div>
                    <strong class="font-medium">Atención:</strong>
                    Tu cuenta de usuario no está vinculada a un perfil de empleado válido. Las métricas no se pueden calcular.
                </div>
            </div>
        </flux:card>
    @else
        <!-- Grid de Métricas (KPIs) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <flux:card>
                <div class="flex items-start justify-between">
                    <div>
                        <flux:text class="text-zinc-500 font-medium text-sm">Llamadas Atendidas</flux:text>
                        <flux:heading size="xl" class="mt-2">{{ $this->metrics['total_calls'] }}</flux:heading>
                    </div>
                    <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                        <flux:icon.phone class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-4 text-xs text-zinc-500">
                    Llamadas conectadas a tu extensión
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-start justify-between">
                    <div>
                        <flux:text class="text-zinc-500 font-medium text-sm">TMO Promedio</flux:text>
                        <flux:heading size="xl" class="mt-2">{{ $this->metrics['avg_talk_time'] }}s</flux:heading>
                    </div>
                    <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                        <flux:icon.clock class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-4 text-xs text-zinc-500">
                    Tiempo de conversación puro
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-start justify-between">
                    <div>
                        <flux:text class="text-zinc-500 font-medium text-sm">AHT (Handle Time)</flux:text>
                        <flux:heading size="xl" class="mt-2">{{ $this->metrics['avg_handle_time'] }}s</flux:heading>
                    </div>
                    <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                        <flux:icon.briefcase class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-4 text-xs text-zinc-500">
                    Conversación + Tiempo de trabajo
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-start justify-between">
                    <div>
                        <flux:text class="text-zinc-500 font-medium text-sm">Llamadas Fallidas</flux:text>
                        <flux:heading size="xl" class="mt-2">{{ $this->metrics['abandoned'] }}</flux:heading>
                    </div>
                    <div class="p-2 bg-red-50 text-red-600 rounded-lg">
                        <flux:icon.phone-x-mark class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-4 text-xs text-zinc-500">
                    Abandonos o rechazos asignados
                </div>
            </flux:card>

        </div>

        <!-- Tabla de Últimas Llamadas -->
        <flux:card class="space-y-4">
            <div>
                <flux:heading size="md">Registro de Llamadas</flux:heading>
                <flux:subheading>Detalle de las interacciones recientes</flux:subheading>
            </div>

            <flux:table :paginate="$this->recentCalls">
                <flux:table.columns>
                    <flux:table.column>Hora Inicio</flux:table.column>
                    <flux:table.column>Cola (Servicio)</flux:table.column>
                    <flux:table.column>Nº Origen</flux:table.column>
                    <flux:table.column>T. Conv.</flux:table.column>
                    <flux:table.column>T. Trabajo</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->recentCalls as $call)
                        <flux:table.row :key="$call->id">
                            <flux:table.cell class="whitespace-nowrap">
                                {{ $call->ivr_started_at?->format('d/m/Y H:i:s') ?? 'N/A' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $call->queue?->name ?? 'Desconocida' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $call->phone_number ?? 'Oculto' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $call->talk_time ?? 0 }}s
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $call->work_time ?? 0 }}s
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $badgeColor = match($call->status) {
                                        'closed' => 'success',
                                        'abandoned', 'rejected', 'aborted' => 'danger',
                                        default => 'warning',
                                    };
                                    $badgeLabel = match($call->status) {
                                        'closed' => 'Atendida',
                                        'abandoned' => 'Abandono',
                                        'rejected' => 'Rechazada',
                                        'aborted' => 'Abortada',
                                        default => ucfirst($call->status),
                                    };
                                @endphp
                                <flux:badge :variant="$badgeColor" size="sm">
                                    {{ $badgeLabel }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-6 text-zinc-500">
                                No se encontraron llamadas registradas en el período seleccionado.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            
            <div class="mt-4">
                {{ $this->recentCalls->links() }}
            </div>
        </flux:card>
    @endif
</div>
