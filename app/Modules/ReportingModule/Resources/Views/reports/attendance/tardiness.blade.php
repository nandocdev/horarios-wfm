<table>
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Número</th>
            <th>Equipo</th>
            <th>Fecha</th>
            <th>Hora Programada</th>
            <th>Hora Real</th>
            <th>Minutos</th>
            <th>Tipo</th>
            <th>Justificación</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->employeeName }}</td>
                <td>{{ $row->employeeNumber }}</td>
                <td>{{ $row->teamName ?? '—' }}</td>
                <td>{{ $row->date }}</td>
                <td>{{ $row->scheduledStart ?? '—' }}</td>
                <td>{{ $row->actualLogin ?? '—' }}</td>
                <td>{{ $row->minutesLate !== null ? $row->minutesLate : '—' }}</td>
                <td>{{ $row->incidentType ?? '—' }}</td>
                <td>{{ $row->justification ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
