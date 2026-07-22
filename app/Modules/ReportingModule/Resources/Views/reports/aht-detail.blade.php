@extends('reporting::reports.layout')

@section('content')
<table>
    <thead>
        <tr>
            <th>Agente</th>
            <th>Cola</th>
            <th>Fecha</th>
            <th>Llamadas</th>
            <th>Talk Time</th>
            <th>Work Time</th>
            <th>Hold Time</th>
            <th>AHT</th>
            <th>Objetivo</th>
            <th>Desviación</th>
            <th>AHT Min</th>
            <th>AHT Max</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->agentName }}</td>
                <td>{{ $row->queueName }}</td>
                <td>{{ $row->date }}</td>
                <td>{{ number_format($row->callsHandled) }}</td>
                <td>{{ gmdate('i:s', (int) $row->avgTalkTime) }}</td>
                <td>{{ gmdate('i:s', (int) $row->avgWorkTime) }}</td>
                <td>{{ gmdate('i:s', (int) $row->avgHoldTime) }}</td>
                <td>{{ gmdate('i:s', (int) $row->aht) }}</td>
                <td>{{ $row->ahtGoal !== null ? gmdate('i:s', $row->ahtGoal) : '—' }}</td>
                <td>{{ $row->deviation !== null ? gmdate('i:s', (int) $row->deviation) : '—' }}</td>
                <td>{{ $row->minAht !== null ? gmdate('i:s', (int) $row->minAht) : '—' }}</td>
                <td>{{ $row->maxAht !== null ? gmdate('i:s', (int) $row->maxAht) : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
