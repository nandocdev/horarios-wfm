{{-- resources/views/pdf/partials/header.blade.php --}}
<table class="no-border">
    <tr>
        <td style="width: 15%;">
            <img src="{{ public_path('images/css-logo.png') }}" style="width: 60px;">
        </td>
        <td style="width: 70%; text-align: center;">
            <strong style="font-size: 13px;">CAJA DE SEGURO SOCIAL</strong><br>
            <strong>DIRECCIÓN DE PERSONAL</strong><br>
            <strong>REPORTE DE INASISTENCIA</strong>
        </td>
        <td style="width: 15%; text-align: right;" class="small">
            F-1<br>
            Cód. 02-968-22
        </td>
    </tr>
</table>

<table class="no-border mt-2">
    <tr>
        <td>
            Panamá, de <span class="field-line">{{ $report->absence_start_date->translatedFormat('F') }}</span>
            de <span class="field-line">{{ $report->absence_start_date->format('Y') }}</span>
        </td>
    </tr>
</table>