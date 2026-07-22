<table>
    <thead>
        <tr>
            <th>Cola</th>
            <th>Fecha</th>
            <th>Recibidos</th>
            <th>Atendidos</th>
            <th>Abandonados</th>
            <th>% Abandono</th>
            <th>AHT</th>
            <th>ASA</th>
            <th>Espera Máx</th>
            <th>Espera Mín</th>
            <th>Abandono Prom</th>
            <th>SLA</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->queueName }}</td>
                <td>{{ $row->date }}</td>
                <td>{{ number_format($row->received) }}</td>
                <td>{{ number_format($row->handled) }}</td>
                <td>{{ number_format($row->abandoned) }}</td>
                <td>{{ number_format($row->abandonmentRate, 1) }}%</td>
                <td>{{ $row->aht !== null ? gmdate('i:s', (int) $row->aht) : '—' }}</td>
                <td>{{ $row->asa !== null ? gmdate('i:s', (int) $row->asa) : '—' }}</td>
                <td>{{ $row->maxWaitTime !== null ? gmdate('i:s', $row->maxWaitTime) : '—' }}</td>
                <td>{{ $row->minWaitTime !== null ? gmdate('i:s', $row->minWaitTime) : '—' }}</td>
                <td>{{ $row->avgAbandonTime !== null ? gmdate('i:s', (int) $row->avgAbandonTime) : '—' }}</td>
                <td>{{ $row->slaPercentage !== null ? number_format($row->slaPercentage, 1).'%' : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
