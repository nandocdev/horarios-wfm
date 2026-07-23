@php
    $count = count($signatures);
    $width = floor(100 / max($count, 1));
@endphp
<table style="width:100%; margin-top:24px;">
    <tr>
        @foreach ($signatures as $signature)
            <td style="width:{{ $width }}%; text-align:center; vertical-align:bottom; padding:0 10px;">
                <div class="signature-line"></div>
                <div style="font-weight:bold;">{{ $signature['label'] }}</div>
                @isset($signature['subtitle'])
                    <div style="font-size:9pt;">{{ $signature['subtitle'] }}</div>
                @endisset
            </td>
        @endforeach
    </tr>
</table>
