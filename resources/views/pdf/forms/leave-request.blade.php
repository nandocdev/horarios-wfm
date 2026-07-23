@extends('pdf.layouts.official-form')

@section('title', 'Formulario para Justificación de Inasistencia')

@section('header')
    @include('pdf.partials.header', [
        'logo'        => $logo ?? public_path('img/logo_full.png'),
        'institution' => 'CAJA DE SEGURO SOCIAL',
        'department'  => 'Dirección Ejecutiva Nacional de Recursos Humanos',
        'formTitle'   => 'FORMULARIO PARA JUSTIFICACIÓN DE INASISTENCIA',
    ])
@endsection

@section('content')

    {{-- Fecha --}}
    <table>
        <tr>
            <td style="width:82%; border:none;"></td>
            <td style="width:18%; border:1px solid #000; text-align:center;">Fecha: {{ $date ?? '__________' }}</td>
        </tr>
    </table>

    <br>

    {{-- Datos del funcionario --}}
    <table>
        <tr>
            <td class="border" style="width:18%;">Cantidad de días</td>
            <td class="border" style="width:12%;">{{ $days ?? '__________' }}</td>
            <td class="border" style="width:22%;">Nombre del funcionario</td>
            <td class="border" colspan="3">{{ $employeeName ?? '______________________________________' }}</td>
        </tr>
        <tr>
            <td class="border">Cargo</td>
            <td class="border" colspan="2">{{ $position ?? '______________________' }}</td>
            <td class="border">No. Empleado</td>
            <td class="border">{{ $employeeNumber ?? '__________' }}</td>
            <td class="border">C.I.P.</td>
        </tr>
        <tr>
            <td class="border">Salario</td>
            <td class="border">{{ $salary ?? '__________' }}</td>
            <td class="border">Sobresueldo</td>
            <td class="border">{{ $bonus ?? '__________' }}</td>
            <td class="border"></td>
            <td class="border"></td>
        </tr>
    </table>

    <br>

    {{-- Clasificación --}}
    <table>
        <tr>
            <td class="border" colspan="2">La ausencia es Justificada</td>
            <td class="border" style="width:8%; text-align:center;">Sí</td>
            <td class="border" style="width:8%; text-align:center;">
                <span class="checkbox">{{ $isJustified === true ? '☑' : '☐' }}</span>
            </td>
            <td class="border" style="width:8%; text-align:center;">No</td>
            <td class="border" style="width:8%; text-align:center;">
                <span class="checkbox">{{ $isJustified === false ? '☑' : '☐' }}</span>
            </td>
        </tr>
    </table>

    <br>

    {{-- Motivos --}}
    <table>
        <tr>
            <td class="border" style="width:15%;">Enfermedad</td>
            <td class="border" style="width:7%; text-align:center;">
                <span class="checkbox">{{ $reason === 'enfermedad' ? '☑' : '☐' }}</span>
            </td>
            <td class="border" style="width:10%;">Común</td>
            <td class="border" style="width:7%; text-align:center;">
                <span class="checkbox">{{ $reason === 'comun' ? '☑' : '☐' }}</span>
            </td>
            <td class="border" style="width:18%;">Riesgo Profesional</td>
            <td class="border" style="width:7%; text-align:center;">
                <span class="checkbox">{{ $reason === 'riesgo_profesional' ? '☑' : '☐' }}</span>
            </td>
            <td class="border" style="width:10%;">Accidente</td>
            <td class="border" style="width:7%; text-align:center;">
                <span class="checkbox">{{ $reason === 'accidente' ? '☑' : '☐' }}</span>
            </td>
            <td class="border" style="width:15%;">Cita Médica</td>
            <td class="border" style="width:7%; text-align:center;">
                <span class="checkbox">{{ $reason === 'cita_medica' ? '☑' : '☐' }}</span>
            </td>
        </tr>
        <tr>
            <td class="border">Duelo</td>
            <td class="border" style="text-align:center;">
                <span class="checkbox">{{ $reason === 'duelo' ? '☑' : '☐' }}</span>
            </td>
            <td class="border">Nacimiento</td>
            <td class="border" style="text-align:center;">
                <span class="checkbox">{{ $reason === 'nacimiento' ? '☑' : '☐' }}</span>
            </td>
            <td class="border">Matrimonio</td>
            <td class="border" style="text-align:center;">
                <span class="checkbox">{{ $reason === 'matrimonio' ? '☑' : '☐' }}</span>
            </td>
            <td class="border">Estudio</td>
            <td class="border" style="text-align:center;">
                <span class="checkbox">{{ $reason === 'estudio' ? '☑' : '☐' }}</span>
            </td>
            <td class="border">Otro</td>
            <td class="border" style="text-align:center;">
                <span class="checkbox">{{ $reason === 'otro' ? '☑' : '☐' }}</span>
            </td>
        </tr>
    </table>

    <br>

    {{-- Evidencias --}}
    <table>
        <tr>
            <td class="border" style="width:18%;">Certificado Médico</td>
            <td class="border" style="width:10%; text-align:center;">
                Sí <span class="checkbox">{{ $hasCertificate === true ? '☑' : '☐' }}</span>
            </td>
            <td class="border" style="width:10%; text-align:center;">
                No <span class="checkbox">{{ $hasCertificate === false ? '☑' : '☐' }}</span>
            </td>
            <td class="border" style="width:18%;">Documentos Sustentadores</td>
            <td class="border" style="width:10%; text-align:center;">
                Sí <span class="checkbox">{{ $hasDocuments === true ? '☑' : '☐' }}</span>
            </td>
            <td class="border" style="width:10%; text-align:center;">
                No <span class="checkbox">{{ $hasDocuments === false ? '☑' : '☐' }}</span>
            </td>
            <td class="border" style="width:15%;">Constancia / Reposo</td>
            <td class="border" style="width:9%; text-align:center;">
                Sí <span class="checkbox">{{ $hasRestCertificate === true ? '☑' : '☐' }}</span>
            </td>
        </tr>
    </table>

    <br>

    {{-- Observaciones --}}
    <table>
        <tr>
            <td class="border" style="height:90px; vertical-align:top;">
                <strong>Observaciones</strong>
                <br>{{ $observations ?? '' }}
            </td>
        </tr>
    </table>

    {{-- Firmas --}}
    @include('pdf.partials.signature-row', [
        'signatures' => [
            ['label' => 'Funcionario'],
            ['label' => 'Jefe Inmediato'],
            ['label' => 'Unidad Ejecutora'],
        ],
    ])

@endsection
