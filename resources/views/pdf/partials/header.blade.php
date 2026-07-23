<table>
    <tr>
        <td style="width:85px; text-align:center; vertical-align:middle; border:1px solid #000; padding:4px;">
            @isset($logo)
                <img src="{{ $logo }}" alt="Logo" style="width:60px;">
            @endisset
        </td>
        <td style="border-top:1px solid #000; border-bottom:1px solid #000; vertical-align:middle; text-align:center; padding:6px;">
            <div style="font-size:11pt; font-weight:bold;">{{ $institution }}</div>
            <div style="font-size:10pt;">{{ $department }}</div>
        </td>
    </tr>
</table>
<table style="margin-top:4px;">
    <tr>
        <td style="border:1px solid #000; text-align:center; font-weight:bold; font-size:12pt; padding:8px;">
            {{ $formTitle }}
        </td>
    </tr>
</table>
