@extends('reporting::reports.layout')

@section('content')
<table>
    <thead>
        <tr>
            <th>Agente</th>
            <th>Número</th>
            <th>Equipo</th>
            <th>Llamadas</th>
            <th>AHT</th>
            <th>Ocupación</th>
            <th>Talk Time</th>
            <th>Disponible</th>
            <th>ACW</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->employeeName }}</td>
                <td>{{ $row->employeeNumber }}</td>
                <td>{{ $row->teamName ?? '—' }}</td>
                <td>{{ number_format($row->callsHandled) }}</td>
                <td>{{ gmdate('i:s', (int) $row->aht) }}</td>
                <td>{{ number_format($row->occupancy, 1) }}%</td>
                <td>{{ gmdate('H:i:s', (int) $row->talkTime) }}</td>
                <td>{{ gmdate('H:i:s', (int) $row->readyTime) }}</td>
                <td>{{ gmdate('H:i:s', (int) $row->acwTime) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
