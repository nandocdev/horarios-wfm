<table>
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Número</th>
            <th>Equipo</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Días</th>
            <th>Observaciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->employeeName }}</td>
                <td>{{ $row->employeeNumber }}</td>
                <td>{{ $row->teamName ?? '—' }}</td>
                <td>{{ $row->startDate }}</td>
                <td>{{ $row->endDate }}</td>
                <td>{{ $row->daysTaken }}</td>
                <td>{{ $row->remarks ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
