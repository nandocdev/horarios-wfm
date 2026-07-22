@extends('reporting::reports.layout')

@section('content')
<table>
    <thead>
        <tr>
            <th>Cola</th>
            <th>Intervalo</th>
            <th>Ofrecidas</th>
            <th>Atendidas</th>
            <th>Abandonadas</th>
            <th>% Abandono</th>
            <th>AHT</th>
            <th>ASA</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->queueName }}</td>
                <td>{{ $row->interval }}</td>
                <td>{{ number_format($row->offered) }}</td>
                <td>{{ number_format($row->handled) }}</td>
                <td>{{ number_format($row->abandoned) }}</td>
                <td>{{ number_format($row->abandonmentRate, 1) }}%</td>
                <td>{{ $row->aht !== null ? gmdate('i:s', (int) $row->aht) : '—' }}</td>
                <td>{{ $row->asa !== null ? gmdate('i:s', (int) $row->asa) : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
