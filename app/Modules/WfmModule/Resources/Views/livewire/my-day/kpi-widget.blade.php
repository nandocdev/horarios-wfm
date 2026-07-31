<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    @if(!$isHistorical)
        <x-wfm.kpi :value="$d['total_calls'] ?? 0" :label="'Llamadas · SLA ' . ($d['sla'] ?? 0) . '%'" icon="phone" color="info" />
        <x-wfm.kpi :value="($d['avg_handle_time'] ? number_format($d['avg_handle_time'], 1) . 's' : '--')" label="AHT (T+ACW)" :comparison="'T ' . ($d['avg_talk_time'] ?? 0) . 's · ACW ' . ($d['avg_acw_time'] ?? 0) . 's'" icon="clock" color="success" />
        @php $adhVal = is_numeric($d['adherence'] ?? null) ? (float) $d['adherence'] : 0; @endphp
        <x-wfm.kpi :value="$adhVal . '%'" label="Adherencia" :trend="$adhVal . '%'" trend-direction="{{ $adhVal >= 80 ? 'up' : 'down' }}" icon="check-badge" :color="$adhVal >= 80 ? 'success' : ($adhVal >= 60 ? 'warning' : 'danger')" />
        <x-wfm.kpi :value="($d['occupancy'] ?? 0) . '%'" label="Ocupación" icon="cpu-chip" />
    @else
        <x-wfm.kpi :value="($d['productivity_pct'] ?? 0) . '%'" label="Productividad" icon="chart-pie" color="info" />
        <x-wfm.kpi :value="$d['handled_calls'] ?? 0" label="Llamadas Atendidas" icon="phone" color="success" />
        <x-wfm.kpi :value="($d['avg_handle_time'] ?? 0) . 's'" label="AHT Promedio" icon="clock" />
        <x-wfm.kpi :value="gmdate('H:i:s', $d['aux_seconds'] ?? 0)" label="Tiempo Auxiliar" icon="clock" color="warning" />
    @endif
</div>
