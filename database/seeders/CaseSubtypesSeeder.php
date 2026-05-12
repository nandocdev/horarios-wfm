<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CaseSubtype;
use Illuminate\Database\Seeder;

class CaseSubtypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $map = [
            'CSQ_FARMACIA' => [
                ['code' => 'FAR_01', 'name' => 'Verificación de existencia de medicamentos específicos', 'description' => 'Verificación de existencia de medicamentos específicos.'],
                ['code' => 'FAR_02', 'name' => 'Estatus de recetas electrónicas activas', 'description' => 'Estatus de recetas electrónicas activas.'],
                ['code' => 'FAR_03', 'name' => 'Requisitos para retiro de medicamentos controlados', 'description' => 'Requisitos y documentación para retiro de medicamentos controlados.'],
                ['code' => 'FAR_04', 'name' => 'Horarios de farmacias en policlínicas', 'description' => 'Consulta de horarios de farmacias en policlínicas periféricas.'],
            ],
            'CSQ_CANCELACION_CITAS' => [
                ['code' => 'CAN_01', 'name' => 'Cancelación de citas', 'description' => 'Cancelación de citas de medicina general o especializada.'],
                ['code' => 'CAN_02', 'name' => 'Notificación por fuerza mayor', 'description' => 'Notificación de imposibilidad de asistencia por fuerza mayor.'],
                ['code' => 'CAN_03', 'name' => 'Reprogramación inmediata', 'description' => 'Solicitudes de reprogramación inmediata si el flujo lo permite.'],
            ],
            'CSQ_CONFIRMACION_CITAS' => [
                ['code' => 'CON_01', 'name' => 'Validación de fecha y hora', 'description' => 'Validación de fecha, hora y consultorio.'],
                ['code' => 'CON_02', 'name' => 'Preparación previa', 'description' => 'Recordatorio de preparación previa (ayuno, exámenes).'],
                ['code' => 'CON_03', 'name' => 'Confirmación de especialista', 'description' => 'Consulta del nombre del especialista asignado.'],
            ],
            'CSQ_CENTRO_CONTACTO' => [
                ['code' => 'CC_01', 'name' => 'Solicitud de cupos medicina general', 'description' => 'Solicitudes de cupos para medicina general.'],
                ['code' => 'CC_02', 'name' => 'Interconsultas', 'description' => 'Tramitación de interconsultas a especialidades.'],
                ['code' => 'CC_03', 'name' => 'Agendamiento servicios técnicos', 'description' => 'Agendamiento de Rayos X, Laboratorio, Ultrasonidos.'],
                ['code' => 'CC_04', 'name' => 'Disponibilidad por unidad', 'description' => 'Información sobre disponibilidad en diferentes unidades ejecutoras.'],
            ],
            'CSQ_INFORMACION_GENERAL' => [
                ['code' => 'INF_01', 'name' => 'Inscripción de dependientes', 'description' => 'Requisitos para inscripción de dependientes.'],
                ['code' => 'INF_02', 'name' => 'Validación de ficha digital', 'description' => 'Validación de ficha digital y carnet de asegurado.'],
                ['code' => 'INF_03', 'name' => 'Ubicación y horarios', 'description' => 'Ubicación y horarios de policlínicas y unidades.'],
                ['code' => 'INF_04', 'name' => 'Trámites de prestaciones', 'description' => 'Trámites de prestaciones económicas (maternidad, incapacidades).'],
            ],
            'CSQ_SIPE' => [
                ['code' => 'SIPE_01', 'name' => 'Recuperación de credenciales empleador', 'description' => 'Recuperación de credenciales de usuario empleador.'],
                ['code' => 'SIPE_02', 'name' => 'Asesoría en planillas', 'description' => 'Asesoría en carga de planillas mensuales.'],
                ['code' => 'SIPE_03', 'name' => 'Reporte de errores técnicos', 'description' => 'Reporte de errores técnicos en el portal SIPE.'],
                ['code' => 'SIPE_04', 'name' => 'Consulta de morosidad', 'description' => 'Consulta de morosidad o paz y salvo patronal.'],
            ],
            'CSQ_QUEJAS' => [
                ['code' => 'QUE_01', 'name' => 'Reporte de mala atención', 'description' => 'Reporte de mala atención por parte del personal.'],
                ['code' => 'QUE_02', 'name' => 'Denuncias por falta de insumos', 'description' => 'Denuncias por falta de insumos o servicios suspendidos.'],
                ['code' => 'QUE_03', 'name' => 'Reclamos por tiempos de espera', 'description' => 'Reclamos por tiempos de espera excesivos.'],
                ['code' => 'QUE_04', 'name' => 'Seguimiento de tickets', 'description' => 'Seguimiento a casos reportados previamente.'],
            ],
        ];

        foreach ($map as $queueName => $subtypes) {
            $queueId = CallQueue::where('name', $queueName)->value('id');
            foreach ($subtypes as $sub) {
                CaseSubtype::firstOrCreate(
                    ['code' => $sub['code'], 'queue_id' => $queueId],
                    [
                        'queue_id' => $queueId,
                        'name' => $sub['name'],
                        'description' => $sub['description'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
