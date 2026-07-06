@extends('reports::layout')

@section('content')

{{-- Encabezado del reporte --}}
<div class="report-info-bar">
    <table>
        <tr>
            <td><strong>Agente:</strong> {{ $employee->first_name }} {{ $employee->last_name }}</td>
            <td><strong>Posición:</strong> {{ $employee->position?->name ?? '—' }}</td>
            <td><strong>Equipo:</strong> {{ $employee->team?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong># Empleado:</strong> {{ $employee->employee_number ?? '—' }}</td>
            <td><strong>Período:</strong> Últimos {{ $days }} días laborados</td>
            <td><strong>Estado:</strong> {{ $employee->status?->name ?? '—' }}</td>
        </tr>
    </table>
</div>

{{-- KPIs principales --}}
<div class="section-title">Resumen de Indicadores</div>
<table class="kpi-grid">
    <tr>
        <td>
            <div class="kpi-card">
                <div class="kpi-value">{{ $summary['avg_adherence'] ?? 0 }}%</div>
                <div class="kpi-label">Adherencia Promedio</div>
            </div>
        </td>
        <td>
            <div class="kpi-card">
                <div class="kpi-value">{{ $summary['avg_occupancy'] ?? 0 }}%</div>
                <div class="kpi-label">Ocupación Promedio</div>
            </div>
        </td>
        <td>
            <div class="kpi-card">
                <div class="kpi-value">{{ $summary['total_calls'] ?? 0 }}</div>
                <div class="kpi-label">Llamadas Totales</div>
            </div>
        </td>
        <td>
            <div class="kpi-card">
                <div class="kpi-value">{{ $summary['total_aux_minutes'] ?? 0 }} min</div>
                <div class="kpi-label">Tiempo Auxiliar</div>
            </div>
        </td>
        <td>
            <div class="kpi-card">
                <div class="kpi-value">
                    {{ $summary['total_calls'] > 0 ? round($summary['total_calls'] / max(count($daily), 1), 0) : 0 }}
                </div>
                <div class="kpi-label">Llamadas / Día</div>
            </div>
        </td>
    </tr>
</table>

{{-- Desempeño diario --}}
<div class="section-title">Desempeño Diario</div>
<table class="data">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Programado</th>
            <th class="right">Conectado</th>
            <th class="right">Productivo</th>
            <th class="center">Entrada</th>
            <th class="right">Retraso</th>
            <th class="right">T. Aux</th>
        </tr>
    </thead>
    <tbody>
        @forelse($daily as $day)
            <tr>
                <td>{{ \Carbon\Carbon::parse($day['date'])->locale('es')->isoFormat('ddd D/M') }}</td>
                <td>{{ ($day['metrics']['total_scheduled_minutes'] ?? 0) > 0 ? round(($day['metrics']['total_scheduled_minutes'] ?? 0) / 60, 1) . 'h' : '—' }}</td>
                <td class="right">{{ ($day['metrics']['total_connected_minutes'] ?? 0) > 0 ? round(($day['metrics']['total_connected_minutes'] ?? 0) / 60, 1) . 'h' : '—' }}</td>
                <td class="right">{{ $day['metrics']['productivity_percentage'] ?? 0 }}%</td>
                <td class="center">
                    @php $att = $day['attendance']; @endphp
                    @if($att['status'] === 'a_tiempo')
                        <span class="badge badge-success">A tiempo</span>
                    @elseif($att['status'] === 'tardanza')
                        <span class="badge badge-danger">Tardanza</span>
                    @elseif($att['status'] === 'ausente')
                        <span class="badge badge-danger">Ausente</span>
                    @elseif($att['status'] === 'excepción')
                        <span class="badge badge-warning">Excepción</span>
                    @elseif($att['status'] === 'no_schedule')
                        <span class="badge badge-info">Sin turno</span>
                    @else
                        <span class="badge badge-info">{{ $att['status'] }}</span>
                    @endif
                </td>
                <td class="right">
                    {{ $att['status'] === 'tardanza' ? ($att['diff_minutes'] ?? 0) . ' min' : '—' }}
                </td>
                <td class="right">{{ ($day['activities']['Not Ready'] ?? 0) + ($day['activities']['AUX'] ?? 0) }} min</td>
            </tr>
        @empty
            <tr><td colspan="7" class="center text-muted">No hay datos de desempeño en el período.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Distribución de Estados --}}
@if(!empty($stateDistribution))
    <div class="section-title">Distribución de Tiempo por Estado</div>
    <table class="data">
        <thead>
            <tr>
                <th>Estado</th>
                <th class="right">Minutos</th>
                <th class="right">Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stateDistribution as $state)
                <tr>
                    <td>{{ $state['label'] }}</td>
                    <td class="right">{{ number_format($state['minutes'], 0) }}</td>
                    <td class="right">{{ number_format($state['percentage'], 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Desviaciones del Turno --}}
@if(!empty($deviations))
    <div class="section-title">Desviaciones del Turno</div>
    <table class="data">
        <thead>
            <tr>
                <th>Día</th>
                <th class="center">Entrada</th>
                <th class="right">Retraso</th>
                <th class="right">T. Aux</th>
                <th class="right">Salida Anticipada</th>
                <th class="right">Conectado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deviations as $dev)
                <tr>
                    <td>{{ $dev['label'] }}</td>
                    <td class="center">
                        @if($dev['entry_status'] === 'a_tiempo')
                            <span class="badge badge-success">A tiempo</span>
                        @elseif($dev['entry_status'] === 'tardanza')
                            <span class="badge badge-danger">{{ $dev['late_minutes'] }} min tarde</span>
                        @elseif($dev['entry_status'] === 'ausente')
                            <span class="badge badge-danger">Ausente</span>
                        @elseif($dev['entry_status'] === 'excepción')
                            <span class="badge badge-warning">Excepción</span>
                        @else
                            <span class="badge badge-info">{{ $dev['entry_status'] }}</span>
                        @endif
                    </td>
                    <td class="right {{ $dev['late_minutes'] > 10 ? 'text-danger' : ($dev['late_minutes'] > 0 ? 'text-warning' : '') }}">
                        {{ $dev['late_minutes'] > 0 ? $dev['late_minutes'] . ' min' : '—' }}
                    </td>
                    <td class="right {{ $dev['aux_minutes'] > 60 ? 'text-warning' : '' }}">
                        {{ $dev['aux_minutes'] }} min
                    </td>
                    <td class="right {{ $dev['early_exit_minutes'] > 10 ? 'text-danger' : '' }}">
                        {{ $dev['early_exit_minutes'] > 0 ? $dev['early_exit_minutes'] . ' min' : '—' }}
                    </td>
                    <td class="right">
                        {{ $dev['connected_minutes'] > 0 ? round($dev['connected_minutes'] / 60, 1) . 'h' : '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Llamadas por Cola --}}
@if(!empty($queueDetail))
    <div class="section-title">Llamadas por Cola</div>
    <table class="data">
        <thead>
            <tr>
                <th>Cola ACD</th>
                <th class="right">Llamadas</th>
                <th class="right">% del Total</th>
            </tr>
        </thead>
        <tbody>
            @php $totalCalls = $summary['total_calls'] ?: 1; @endphp
            @foreach($queueDetail as $queue)
                <tr>
                    <td>{{ $queue['name'] }}</td>
                    <td class="right">{{ $queue['total_calls'] }}</td>
                    <td class="right">{{ round(($queue['total_calls'] / $totalCalls) * 100) }}%</td>
                </tr>
            @endforeach
            <tr class="grandtotal">
                <td>Total</td>
                <td class="right">{{ $summary['total_calls'] }}</td>
                <td class="right">100%</td>
            </tr>
        </tbody>
    </table>
@endif

{{-- Firma --}}
<div class="mt-2" style="padding-top: 20px;">
    <table class="w-full">
        <tr>
            <td class="text-center" style="padding-top: 30px; border-top: 1px solid #cbd5e1; width: 40%;">
                <span class="text-muted" style="font-size: 7pt;">Supervisor / Coordinador</span>
            </td>
            <td style="width: 20%;"></td>
            <td class="text-center" style="padding-top: 30px; border-top: 1px solid #cbd5e1; width: 40%;">
                <span class="text-muted" style="font-size: 7pt;">Agente</span>
            </td>
        </tr>
    </table>
</div>

@endsection
