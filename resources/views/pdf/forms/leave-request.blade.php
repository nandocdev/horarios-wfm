{{-- resources/views/pdf/forms/leave-request.blade.php --}}
@extends('pdf.layouts.official-form')

@section('title', 'Reporte de Inasistencia - ' . $report->employee_number)

@section('content')
    @include('pdf.partials.header', ['report' => $report])

    <p class="mt-2">Señor Jefe del Departamento de Ingresos Cambios y Separaciones:</p>

    <p>
        Cúmpleme llevar a su conocimiento, que el (los) día(s)
        <span class="field-line" style="min-width: 300px;">{{ $report->absence_start_date->format('d/m/Y') }}</span>
        TOTAL DIA(S) <span class="field-line" style="min-width: 60px;">{{ $report->absence_total_days }}</span>
    </p>

    <p>dejó de asistir a sus labores el siguiente servidor público:</p>

    <table class="no-border">
        <tr>
            <td style="width: 60%;">Cargo: <span class="field-line" style="min-width: 250px;">{{ $report->employee_position }}</span></td>
            <td style="width: 40%;">No. de Empleado: <span class="field-line">{{ $report->employee_number }}</span></td>
        </tr>
    </table>

    <table class="no-border mt-1">
        <tr>
            <td style="width: 34%;">No. de C.I.P.: <span class="field-line">{{ $report->cip_number }}</span></td>
            <td style="width: 33%;">Sueldo Base B/. <span class="field-line">{{ number_format((float) $report->base_salary, 2) }}</span></td>
            <td style="width: 33%;">Sobresueldo B/. <span class="field-line">{{ number_format((float) $report->salary_supplement, 2) }}</span></td>
        </tr>
    </table>

    <table class="bordered mt-2 text-center">
        <tr>
            <td rowspan="2" style="width: 20%;">
                <strong>INASISTENCIA JUSTIFICADA</strong><br><br>
                SI <span class="checkbox">{{ $report->is_justified ? 'X' : '' }}</span>
                &nbsp;&nbsp;
                NO <span class="checkbox">{{ ! $report->is_justified ? 'X' : '' }}</span>
            </td>
            <td colspan="2" style="width: 45%;"><strong>ENFERMEDAD</strong></td>
            <td style="width: 15%;"><strong>DUELO</strong></td>
            <td style="width: 20%;"><strong>NACIMIENTO DE HIJO</strong></td>
        </tr>
        <tr>
            <td style="width: 22.5%;">
                COMÚN<br>
                <span class="checkbox">{{ $report->reason_type === \App\Enums\AbsenceReasonType::CommonIllness ? 'X' : '' }}</span>
            </td>
            <td style="width: 22.5%;">
                RIESGOS<br>
                <span class="checkbox">{{ $report->reason_type === \App\Enums\AbsenceReasonType::OccupationalRisk ? 'X' : '' }}</span>
            </td>
            <td><span class="checkbox">{{ $report->reason_type === \App\Enums\AbsenceReasonType::Bereavement ? 'X' : '' }}</span></td>
            <td><span class="checkbox">{{ $report->reason_type === \App\Enums\AbsenceReasonType::ChildBirth ? 'X' : '' }}</span></td>
        </tr>
    </table>

    <table class="bordered text-center">
        <tr>
            <td colspan="2" style="width: 60%;"><strong>Certificado Médico adjunto al original</strong></td>
            <td colspan="2" style="width: 40%;"><strong>Sustendadores</strong></td>
        </tr>
        <tr>
            <td style="width: 30%;">SI <span class="checkbox">{{ $report->medical_certificate_attached === true ? 'X' : '' }}</span></td>
            <td style="width: 30%;">NO <span class="checkbox">{{ $report->medical_certificate_attached === false ? 'X' : '' }}</span></td>
            <td style="width: 20%;">SI <span class="checkbox">{{ $report->has_witnesses === true ? 'X' : '' }}</span></td>
            <td style="width: 20%;">NO <span class="checkbox">{{ $report->has_witnesses === false ? 'X' : '' }}</span></td>
        </tr>
    </table>

    <p class="mt-2">
        <strong>Observaciones:</strong> *<br>
        <span style="border-bottom: 1px solid #000; display: block; min-height: 14px;">{{ $report->observations }}</span>
        <span style="border-bottom: 1px solid #000; display: block; min-height: 14px;">&nbsp;</span>
    </p>

    <p class="mt-1">Atentamente,</p>

    @include('pdf.partials.signature-row', [
        'columns' => [
            'Firma del Empleado',
            'Jefe del Departamento de: ' . $report->department_head_name,
            'Unidad Ejecutora: ' . $report->executive_unit,
        ],
    ])

    @include('pdf.partials.footer')

    <div style="border-top: 2px dashed #000; margin-top: 10px; padding-top: 8px;">
        <p class="text-center"><strong>USO EXCLUSIVO DEL DEPARTAMENTO DE INGRESOS, CAMBIOS Y SEPARACIONES</strong></p>

        <table class="no-border">
            <tr>
                <td style="width: 25%;">Clave del descuento: <span class="field-line">{{ $report->discount_code }}</span></td>
                <td style="width: 25%;">Desc.: <span class="field-line">{{ $report->discount_description }}</span></td>
                <td style="width: 25%;">Monto: <span class="field-line">{{ $report->discount_amount ? number_format((float) $report->discount_amount, 2) : '' }}</span></td>
                <td style="width: 25%;">Saldo: <span class="field-line">{{ $report->discount_balance ? number_format((float) $report->discount_balance, 2) : '' }}</span></td>
            </tr>
        </table>

        @include('pdf.partials.signature-row', [
            'columns' => [
                'Firma del Contador: ' . $report->accountant_name,
                'Efectuar Descuento Quincena: ' . ($report->discount_biweekly_authorized ? 'SI' : 'NO'),
            ],
        ])
    </div>
@endsection