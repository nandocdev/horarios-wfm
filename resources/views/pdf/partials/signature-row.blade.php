@php
    if (isset($columns)) {
        // Formato legacy: array de strings
        $count = count($columns);
        $items = $columns;
    } else {
        // Formato nuevo: array de arrays con 'label' y opcional 'subtitle'
        $count = count($signatures);
        $items = array_map(fn ($s) => $s['label'] . (isset($s['subtitle']) ? "\n" . $s['subtitle'] : ''), $signatures);
    }
    $width = floor(100 / max($count, 1));
@endphp
<table style="width:100%; margin-top:24px;">
    <tr>
        @foreach ($items as $item)
            <td style="width:{{ $width }}%; text-align:center; vertical-align:bottom; padding:0 10px;">
                <div class="signature-line"></div>
                <div style="font-weight:bold;">{!! nl2br(e($item)) !!}</div>
            </td>
        @endforeach
    </tr>
</table>
