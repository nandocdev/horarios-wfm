@extends('reporting::reports.layout')

@section('content')
<table>
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Número</th>
            <th>Equipo</th>
            <th>Fecha</th>
            <th>Origen</th>
            <th>Causa</th>
            <th>Justificado</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Minutos</th>
            <th>Observaciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->employeeName }}</td>
                <td>{{ $row->employeeNumber }}</td>
                <td>{{ $row->teamName ?? '—' }}</td>
                <td>{{ $row->date }}</td>
                <td>{{ $row->originType === 'schedule_exception' ? 'Planificado' : 'Incidencia' }}</td>
                <td>{{ $row->causeName }}</td>
                <td>{{ $row->isJustified ? 'Sí' : 'No' }}</td>
                <td>{{ $row->startAt ?? '—' }}</td>
                <td>{{ $row->endAt ?? '—' }}</td>
                <td>{{ $row->minutesAbsent !== null ? $row->minutesAbsent : '—' }}</td>
                <td>{{ $row->remarks ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
