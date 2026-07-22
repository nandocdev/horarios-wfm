<table>
    <thead>
        <tr>
            @foreach ($headers as $header)
                <th>{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                @if (is_array($row))
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                @else
                    <td>{{ $row }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
