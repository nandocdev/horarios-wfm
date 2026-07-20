<div class="space-y-6">
    <x-wfm.page-header title="Dashboard General" description="Monitoreo macro de la operación y métricas de servicio.">
        <x-slot:actions>
            <flux:select wire:model.live="dateRange" class="!w-40">
                <flux:select.option value="today">Hoy</flux:select.option>
                <flux:select.option value="this_week">Esta Semana</flux:select.option>
                <flux:select.option value="this_month">Este Mes</flux:select.option>
            </flux:select>
        </x-slot:actions>
    </x-wfm.page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <x-wfm.kpi :value="$this->metrics['total_volume']" label="Volumen Inbound" icon="inbox-arrow-down" comparison="Llamadas que ingresaron al IVR" />
        <x-wfm.kpi :value="$this->metrics['sla'] . '%'" label="Service Level (SLA)" icon="chart-bar" :color="$this->metrics['sla'] >= 80 ? 'success' : 'danger'" comparison="Atendidas en &lt; 20 seg" />
        <x-wfm.kpi :value="$this->metrics['abandon_rate'] . '%'" label="Tasa de Abandono" icon="phone-x-mark" :color="$this->metrics['abandon_rate'] <= 5 ? 'success' : 'danger'" comparison="Proporción de llamadas perdidas" />
        <x-wfm.kpi :value="$this->metrics['total_handled']" label="Llamadas Atendidas" icon="phone" color="info" comparison="Conectadas con un agente" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-wfm.section title="Top Performers" description="Agentes con mayor volumen de atención.">
            @forelse($this->topPerformers as $index => $performer)
                <div class="flex items-center justify-between p-3 rounded border mb-2 last:mb-0 {{ $index === 0 ? 'bg-wfm-warning/5 border-wfm-warning/20' : 'bg-wfm-surface border-wfm-surface-border' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded flex items-center justify-center font-bold text-xs {{ $index === 0 ? 'bg-wfm-warning/20 text-wfm-warning' : 'bg-wfm-surface-hover text-wfm-surface-muted' }}">
                            {{ $index + 1 }}
                        </div>
                        <div>
                            <p class="text-xs font-medium text-wfm-navy-800 dark:text-white">{{ $performer->employee->full_name ?? 'Desconocido' }}</p>
                            <p class="text-[10px] text-wfm-surface-muted">TMO: {{ round($performer->avg_tmo, 0) }}s</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-wfm-navy-800 dark:text-white">{{ $performer->total_calls }}</p>
                        <p class="text-[10px] text-wfm-surface-muted">llamadas</p>
                    </div>
                </div>
            @empty
                <x-wfm.empty icon="chart-bar" message="No hay datos suficientes en el período seleccionado." />
            @endforelse
        </x-wfm.section>

        <x-wfm.section title="Análisis de Volumen" description="Esta visualización gráfica será integrada en una fase posterior.">
            <div class="flex flex-col items-center justify-center min-h-[250px] text-center">
                <flux:icon.chart-bar-square class="w-8 h-8 text-wfm-surface-muted mb-2" />
                <p class="text-xs text-wfm-surface-muted">Próximamente: Heatmap de volumen por hora y cola.</p>
            </div>
        </x-wfm.section>
    </div>
</div>
