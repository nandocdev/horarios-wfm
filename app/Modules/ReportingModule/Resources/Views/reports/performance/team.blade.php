@extends('reporting::reports.layout')

@section('content')
<table>
    <thead>
        <tr>
            <th>Equipo</th>
            <th>Agentes</th>
            <th>Llamadas</th>
            <th>AHT Prom</th>
            <th>Ocupación Prom</th>
            <th>Adherencia Prom</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->teamName }}</td>
                <td>{{ $row->agentCount }}</td>
                <td>{{ number_format($row->totalCalls) }}</td>
                <td>{{ gmdate('i:s', (int) $row->avgAht) }}</td>
                <td>{{ number_format($row->avgOccupancy, 1) }}%</td>
                <td>{{ number_format($row->avgAdherence, 1) }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
