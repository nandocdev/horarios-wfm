@extends('reporting::reports.layout')

@section('content')
<table>
    <thead>
        <tr>
            <th>Causa</th>
            <th>Código</th>
            <th>Justificado</th>
            <th>Ocurrencias</th>
            <th>Minutos perdidos</th>
            <th>Empleados afectados</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->causeName }}</td>
                <td>{{ $row->shortCode }}</td>
                <td>{{ $row->isExcused ? 'Sí' : 'No' }}</td>
                <td>{{ number_format($row->totalOccurrences) }}</td>
                <td>{{ number_format($row->totalMinutesLost) }}</td>
                <td>{{ number_format($row->employeesAffected) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
