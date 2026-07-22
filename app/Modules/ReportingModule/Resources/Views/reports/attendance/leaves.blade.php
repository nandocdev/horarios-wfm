<table>
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Número</th>
            <th>Equipo</th>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Justificado</th>
            <th>Estado</th>
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
                <td>{{ $row->leaveType }}</td>
                <td>{{ $row->isExcused ? 'Sí' : 'No' }}</td>
                <td>{{ $row->status }}</td>
                <td>{{ $row->minutes !== null ? $row->minutes : '—' }}</td>
                <td>{{ $row->remarks ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
