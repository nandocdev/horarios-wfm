@extends('reporting::reports.layout')

@section('content')
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Agente</th>
            <th>Número</th>
            <th>Equipo</th>
            <th>Llamadas</th>
            <th>AHT</th>
            <th>Ocupación</th>
            <th>Adherencia</th>
            <th>Score</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->position }}</td>
                <td>{{ $row->employeeName }}</td>
                <td>{{ $row->employeeNumber }}</td>
                <td>{{ $row->teamName ?? '—' }}</td>
                <td>{{ number_format($row->callsHandled) }}</td>
                <td>{{ gmdate('i:s', (int) $row->aht) }}</td>
                <td>{{ number_format($row->occupancy, 1) }}%</td>
                <td>{{ number_format($row->adherence, 1) }}%</td>
                <td>{{ number_format($row->score, 1) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
