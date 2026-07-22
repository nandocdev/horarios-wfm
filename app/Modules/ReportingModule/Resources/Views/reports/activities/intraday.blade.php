@extends('reporting::reports.layout')

@section('content')
<table>
    <thead>
        <tr>
            <th>Agente</th>
            <th>Fecha</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Actividad</th>
            <th>Productiva</th>
            <th>Notas</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->employeeName }}</td>
                <td>{{ $row->date }}</td>
                <td>{{ $row->startTime }}</td>
                <td>{{ $row->endTime }}</td>
                <td>{{ $row->activityName }}</td>
                <td>{{ $row->isProductive ? 'Sí' : 'No' }}</td>
                <td>{{ $row->notes ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
