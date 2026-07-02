<div class="space-y-6">
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

    @if(!$this->employee)
        <flux:card class="bg-red-50 border-red-200">
            <div class="flex items-center gap-3 text-red-800">
                <flux:icon.exclamation-triangle class="w-6 h-6" />
                <div>
                    <strong class="font-medium">Atención:</strong>
                    Tu cuenta de usuario no está vinculada a un perfil de empleado válido.
                </div>
            </div>
        </flux:card>
    @else
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
                <div class="mt-4 text-xs text-zinc-500">Eventos registrados</div>
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
                <div class="mt-4 text-xs text-zinc-500">Tiempo de conversación</div>
            </flux:card>

            <flux:card>
                <div class="flex items-start justify-between">
                    <div>
                        <flux:text class="text-zinc-500 font-medium text-sm">AHT</flux:text>
                        <flux:heading size="xl" class="mt-2">{{ $this->metrics['avg_handle_time'] }}s</flux:heading>
                    </div>
                    <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                        <flux:icon.briefcase class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-4 text-xs text-zinc-500">Conversación + Trabajo</div>
            </flux:card>

            <flux:card>
                <div class="flex items-start justify-between">
                    <div>
                        <flux:text class="text-zinc-500 font-medium text-sm">Estado Actual</flux:text>
                        <flux:heading size="xl" class="mt-2">
                            {{ $this->state?->current_state ?? '—' }}
                        </flux:heading>
                    </div>
                    <div class="p-2 bg-zinc-50 text-zinc-600 rounded-lg">
                        <flux:icon.user class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-4 text-xs text-zinc-500">Estado en tiempo real</div>
            </flux:card>
        </div>

        <flux:card class="space-y-4">
            <div>
                <flux:heading size="md">Registro de Eventos</flux:heading>
                <flux:subheading>Eventos de llamada recientes</flux:subheading>
            </div>

            <flux:table :paginate="$this->recentCalls">
                <flux:table.columns>
                    <flux:table.column>Fecha</flux:table.column>
                    <flux:table.column>Tipo</flux:table.column>
                    <flux:table.column>Teléfono</flux:table.column>
                    <flux:table.column>Cola</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->recentCalls as $event)
                        <flux:table.row :key="$event->id">
                            <flux:table.cell>{{ $event->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</flux:table.cell>
                            <flux:table.cell>{{ $event->type ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $event->phone_number ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $event->queue_name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm">{{ $event->status ?? '—' }}</flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-6 text-zinc-500">
                                No hay eventos en el período seleccionado.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">{{ $this->recentCalls->links() }}</div>
        </flux:card>
    @endif
</div>
