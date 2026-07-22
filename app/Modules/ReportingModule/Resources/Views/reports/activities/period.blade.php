<table>
    <thead>
        <tr>
            <th>Entidad</th>
            <th>Actividad</th>
            <th>Minutos</th>
            <th>Productiva</th>
            <th>% Cumplimiento</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->entityName }}</td>
                <td>{{ $row->activityName }}</td>
                <td>{{ $row->totalMinutes }}</td>
                <td>{{ $row->isProductive ? 'Sí' : 'No' }}</td>
                <td>{{ $row->compliancePct !== null ? number_format($row->compliancePct, 1).'%' : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
