<table>
    <thead>
        <tr>
            <th>Agente</th>
            <th>Días Prog.</th>
            <th>Ausencias</th>
            <th>Tardanzas</th>
            <th>Permisos</th>
            <th>Vacaciones</th>
            <th>% Asistencia</th>
            <th>% Tardanza</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->entityName }}</td>
                <td>{{ $row->totalScheduledDays }}</td>
                <td>{{ $row->totalAbsences }}</td>
                <td>{{ $row->totalTardiness }}</td>
                <td>{{ $row->totalLeaves }}</td>
                <td>{{ $row->totalVacationDays }}</td>
                <td>{{ number_format($row->attendanceRate, 1) }}%</td>
                <td>{{ number_format($row->tardinessRate, 1) }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>
