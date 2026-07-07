<div class="space-y-8">
    <!-- Header y Controles -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <flux:heading size="xl">Dashboard General</flux:heading>
            <flux:subheading>Monitoreo macro de la operación y métricas de servicio</flux:subheading>
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="dateRange">
                <flux:select.option value="today">Hoy</flux:select.option>
                <flux:select.option value="this_week">Esta Semana</flux:select.option>
                <flux:select.option value="this_month">Este Mes</flux:select.option>
            </flux:select>
        </div>
    </div>

    <!-- Grid de Métricas Macro (KPIs) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-zinc-500 font-medium text-sm">Volumen Inbound</flux:text>
                    <flux:heading size="xl" class="mt-2">{{ $this->metrics['total_volume'] }}</flux:heading>
                </div>
                <div class="p-2 bg-slate-50 text-slate-600 rounded-md">
                    <flux:icon.inbox-arrow-down class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 text-xs text-zinc-500">
                Llamadas que ingresaron al IVR
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-zinc-500 font-medium text-sm">Service Level (SLA)</flux:text>
                    <flux:heading size="xl" class="mt-2">{{ $this->metrics['sla'] }}%</flux:heading>
                </div>
                <div class="p-2 {{ $this->metrics['sla'] >= 80 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }} rounded-md">
                    <flux:icon.chart-bar class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 text-xs text-zinc-500">
                Atendidas en < 20 seg
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-zinc-500 font-medium text-sm">Tasa de Abandono</flux:text>
                    <flux:heading size="xl" class="mt-2">{{ $this->metrics['abandon_rate'] }}%</flux:heading>
                </div>
                <div class="p-2 {{ $this->metrics['abandon_rate'] <= 5 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }} rounded-md">
                    <flux:icon.phone-x-mark class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 text-xs text-zinc-500">
                Proporción de llamadas perdidas
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-zinc-500 font-medium text-sm">Llamadas Atendidas</flux:text>
                    <flux:heading size="xl" class="mt-2">{{ $this->metrics['total_handled'] }}</flux:heading>
                </div>
                <div class="p-2 bg-blue-50 text-blue-600 rounded-md">
                    <flux:icon.phone class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 text-xs text-zinc-500">
                Conectadas con un agente
            </div>
        </flux:card>

    </div>

    <!-- Sección Inferior: Top Performers y Heatmap (Placeholder para futuro gráfico) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        
        <!-- Top Performers -->
        <flux:card class="space-y-4">
            <div>
                <flux:heading size="md">Top Performers</flux:heading>
                <flux:subheading>Agentes con mayor volumen de atención</flux:subheading>
            </div>

            <div class="space-y-4 mt-4">
                @forelse($this->topPerformers as $index => $performer)
                    <div class="flex items-center justify-between p-3 rounded-md {{ $index === 0 ? 'bg-amber-50 border border-amber-100' : 'bg-white border border-zinc-100' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm {{ $index === 0 ? 'bg-amber-200 text-amber-800' : 'bg-zinc-100 text-zinc-600' }}">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <div class="font-medium text-sm text-zinc-900">{{ $performer->employee->full_name ?? 'Desconocido' }}</div>
                                <div class="text-xs text-zinc-500">TMO: {{ round($performer->avg_tmo, 0) }}s</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-lg text-zinc-900">{{ $performer->total_calls }}</div>
                            <div class="text-xs text-zinc-500">llamadas</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-zinc-500 text-sm">
                        No hay datos suficientes en el período seleccionado.
                    </div>
                @endforelse
            </div>
        </flux:card>

        <!-- Espacio para Análisis Visual (Futuro Heatmap) -->
        <flux:card class="flex flex-col items-center justify-center min-h-[300px] border-dashed bg-zinc-50">
            <div class="text-center space-y-2">
                <flux:icon.chart-bar-square class="w-10 h-10 mx-auto text-zinc-300" />
                <flux:heading size="md" class="text-zinc-500">Análisis de Volumen (Heatmap)</flux:heading>
                <flux:text class="text-zinc-400 text-sm">Esta visualización gráfica será integrada en una fase posterior.</flux:text>
            </div>
        </flux:card>
    </div>
</div>
