@extends('pdf.layouts.official-form')

@section('title', 'Reporte de Inasistencia - ' . ($report->employee_number ?? ''))

@section('content')
        @include('pdf.partials.header')

        {{-- Fecha: Panamá, __ de _____ de 20__ --}}
        <table class="no-border mt-1">
            <tr>
            <td>
                </td>
                <td>
                <p class="mt-1 text-right">
                    Panamá,
                    <span class="field-line" style="min-width:30px;">{{ $report->absence_start_date->format('d') }}</span>
                    de
                <span class="field-line" style="min-width:100px;">{{ $report->absence_start_date->translatedFormat('F') }}</span>
                de
                <span class="field-line" style="min-width:40px;">{{ $report->absence_start_date->format('Y') }}</span>
            </p>
        </td>
            </tr>
        </table>
        {{-- Saludo con campo para nombre del jefe --}}
        <p class="mt-1">
            Señor Jefe del Departamento de Ingresos Cambios y Separaciones:
            <span class="field-line" style="min-width:250px;">{{ $report->department_head_name }}</span>
        </p>

        {{-- Cuerpo --}}
        <p class="mt-1">
            Cúmpleme llevar a su conocimiento, que el (los) día(s)
            <span class="field-line" style="min-width:180px;">{{ $report->absence_start_date->format('d/m/Y') }}</span>
            <strong>TOTAL DIA(S)</strong>
            <span class="field-line" style="min-width:50px;">{{ $report->absence_total_days }}</span>
        </p>

        <p class="mt-1">
            dejó de asistir a sus labores el siguiente servidor público:
            <span class="field-line" style="min-width:300px;">{{ $report->employee_name ?? '' }}</span>
        </p>

        {{-- Cargo + No. Empleado --}}
        <table class="no-border mt-1">
            <tr>
                <td style="width:55%;">
                    <strong>Cargo:</strong>
                    <span class="field-line" style="min-width:200px;">{{ $report->employee_position }}</span>
                </td>
                <td style="width:45%;">
                    <strong>No. de Empleado:</strong>
                    <span class="field-line" style="min-width:120px;">{{ $report->employee_number }}</span>
                </td>
            </tr>
        </table>

        {{-- C.I.P. + Sueldo Base + Sobresueldo --}}
        <table class="no-border mt-1">
            <tr>
                <td style="width:34%;">
                    <strong>No. de C.I.P.:</strong>
                    <span class="field-line" style="min-width:80px;">{{ $report->cip_number }}</span>
                </td>
                <td style="width:33%;">
                    <strong>Sueldo Base B/.</strong>
                    <span class="field-line" style="min-width:80px;">{{ number_format((float) $report->base_salary, 2) }}</span>
                </td>
                <td style="width:33%;">
                    <strong>Sobresueldo B/.</strong>
                    <span class="field-line" style="min-width:80px;">{{ number_format((float) $report->salary_supplement, 2) }}</span>
                </td>
            </tr>
        </table>

        <hr class="mt-2">

        {{-- Clasificación de la Inasistencia --}}
        <p class="text-bold mt-1">Clasificación de la Inasistencia</p>

        <table class="bordered text-center" style="margin-top:4px;">
            <tr>
                <td rowspan="2" style="width:20%; vertical-align:middle;">
                    <strong>INASISTENCIA JUSTIFICADA</strong><br><br>
                    SI <span class="checkbox">{{ $report->is_justified ? 'X' : '' }}</span>
                    &nbsp;&nbsp;
                    NO <span class="checkbox">{{ !$report->is_justified ? 'X' : '' }}</span>
                </td>
                <td colspan="2" style="width:40%;"><strong>ENFERMEDAD</strong></td>
                <td style="width:15%;"><strong>DUELO</strong></td>
                <td style="width:25%;"><strong>NACIMIENTO DE HIJO</strong></td>
            </tr>
            <tr>
                <td style="width:20%;">
                    <strong>COMÚN</strong><br>
                    <span class="checkbox">{{ $report->reason_type === \App\Enums\AbsenceReasonType::CommonIllness ? 'X' : '' }}</span>
                </td>
                <td style="width:20%;">
                    <strong>RIESGOS</strong><br>
                    <span class="checkbox">{{ $report->reason_type === \App\Enums\AbsenceReasonType::OccupationalRisk ? 'X' : '' }}</span>
                </td>
                <td>
                    <span class="checkbox">{{ $report->reason_type === \App\Enums\AbsenceReasonType::Bereavement ? 'X' : '' }}</span>
                </td>
                <td>
                    <span class="checkbox">{{ $report->reason_type === \App\Enums\AbsenceReasonType::ChildBirth ? 'X' : '' }}</span>
                </td>
            </tr>
        </table>

        {{-- Documentos --}}
        <table class="bordered text-center" style="margin-top:4px;">
            <tr>
                <td colspan="2" style="width:50%;"><strong>Certificado Médico adjunto al original</strong></td>
                <td colspan="2" style="width:50%;"><strong>Sustentadores</strong></td>
            </tr>
            <tr>
                <td style="width:25%;">
                    SI <span class="checkbox">{{ $report->medical_certificate_attached ? 'X' : '' }}</span>
                </td>
                <td style="width:25%;">
                    NO <span class="checkbox">{{ !$report->medical_certificate_attached ? 'X' : '' }}</span>
                </td>
                <td style="width:25%;">
                    SI <span class="checkbox">{{ $report->has_witnesses ? 'X' : '' }}</span>
                </td>
                <td style="width:25%;">
                    NO <span class="checkbox">{{ !$report->has_witnesses ? 'X' : '' }}</span>
                </td>
            </tr>
        </table>

        <hr class="mt-2">

        {{-- Observaciones --}}
        <p class="mt-1"><strong>Observaciones:</strong> *</p>
        <p class="no-border" style="border-bottom:1px solid #000; min-height:14px;">{{ $report->observations }}</p>
        <p class="no-border" style="border-bottom:1px solid #000; min-height:14px;">&nbsp;</p>
        <p class="no-border" style="border-bottom:1px solid #000; min-height:14px;">&nbsp;</p>

        <hr class="mt-2">

        {{-- Atentamente --}}
        <p class="mt-1"><strong>Atentamente,</strong></p>

        {{-- Firmas --}}
        @include('pdf.partials.signature-row', [
        'columns' => [
            'Firma del Empleado',
            'Jefe del Departamento de: ' . ($report->department_head_name ?? '____________________'),
            'Unidad Ejecutora: ' . ($report->executive_unit ?? '____________________'),
        ],
    ])

        {{-- Nota --}}
        <table class="no-border mt-2 small">
            <tr>
                <td>
                    <strong>NOTA:</strong> Envíe este documento original al Departamento de Ingresos Cambios y Separaciones
                    de la Dirección de Personal. Conserve 1 copia. Envíe una copia a la Unidad o Departamento de Personal
                    de su Unidad Ejecutora.
                    <br><br>
                    *Si la inasistencia es justificada, indique en el renglón de observaciones, claramente los motivos que
                    justifiquen la misma, ya que aquellas que no estén plenamente justificadas, serán consideradas como
                    inasistencias injustificadas.
                </td>
            </tr>
        </table>

        <hr class="mt-2">

        {{-- USO EXCLUSIVO --}}
        <p class="text-center mt-1"><strong>USO EXCLUSIVO DEL DEPARTAMENTO DE INGRESOS, CAMBIOS Y SEPARACIONES</strong></p>

        <table class="no-border">
            <tr>
                <td style="width:30%;">
                    <strong>Clave del descuento:</strong>
                    <span class="field-line" style="min-width:60px;">{{ $report->discount_code ?? '' }}</span>
                </td>
                <td style="width:25%;">
                    <strong>Desc.:</strong>
                    <span class="field-line" style="min-width:60px;">{{ $report->discount_description ?? '' }}</span>
                </td>
                <td style="width:25%;">
                    <strong>Monto:</strong>
                    <span class="field-line" style="min-width:60px;">{{ $report->discount_amount ? number_format((float) $report->discount_amount, 2) : '' }}</span>
                </td>
                <td style="width:20%;">
                    <strong>Saldo:</strong>
                    <span class="field-line" style="min-width:60px;">{{ $report->discount_balance ? number_format((float) $report->discount_balance, 2) : '' }}</span>
                </td>
            </tr>
        </table>

        <table class="no-border mt-2">
            <tr>
                <td style="width:50%;">
                    <strong>Firma del Contador:</strong>
                    <span class="field-line" style="min-width:180px;">{{ $report->accountant_name }}</span>
                </td>
                <td style="width:50%;">
                    <strong>Efectuar Descuento Quincena:</strong>
                    <span class="field-line" style="min-width:60px;">{{ $report->discount_biweekly_authorized ? 'SI' : 'NO' }}</span>
                </td>
            </tr>
        </table>
@endsection
