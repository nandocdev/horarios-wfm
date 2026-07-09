<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Actions;

use App\Modules\CoreModule\Models\User;
use App\Modules\OperationsModule\Models\AttendanceIncident;
use App\Modules\OperationsModule\Models\IncidentType;
use App\Modules\WfmModule\Notifications\AttendanceIncidentNotification;
use App\Shared\Contracts\Employees\EmployeeInterface;
use App\Shared\DTOs\NotificationDTO;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * Acción para reconciliar la asistencia de un empleado y generar incidentes operativos.
 *
 * [RIESGOS]
 * - Duplicidad de incidentes: Se mitiga buscando por employee_id, date y type.
 * - Falsos positivos: Depende de la calidad de datos en ConnectModule (Cisco).
 */
final class ReconcileEmployeeAttendanceAction
{
    public function __construct(
        private readonly GetEmployeePerformanceAction $performanceAction
    ) {}

    /**
     * Ejecuta la reconciliación para un empleado en una fecha específica.
     */
    public function execute(EmployeeInterface $employee, CarbonInterface $date): array
    {
        $carbonDate = Carbon::instance($date);
        $performance = $this->performanceAction->execute($employee, $carbonDate);
        $attendance = $performance->attendance;
        $results = [];

        // 1. Reconciliar Tardanza
        if ($attendance['status'] === 'tardanza') {
            $incident = $this->recordIncident(
                $employee,
                'LATE',
                $date,
                $attendance['scheduled_entry'],
                $attendance['actual_entry'],
                "Tardanza detectada automáticamente: {$attendance['diff_minutes']} minutos."
            );
            if ($incident) {
                $results[] = 'LATE';
            }
        }

        // 2. Reconciliar Ausencia (Solo si el turno ya debió haber terminado o si es un día pasado)
        if ($attendance['status'] === 'ausente') {
            if ($this->shouldMarkAsAbsent($attendance, $date)) {
                $incident = $this->recordIncident(
                    $employee,
                    'ABSENT',
                    $date,
                    $attendance['scheduled_entry'],
                    null,
                    'Ausencia detectada: No se registraron marcas en Cisco para la jornada programada.'
                );
                if ($incident) {
                    $results[] = 'ABSENT';
                }
            }
        }

        return $results;
    }

    /**
     * Determina si ya es momento de marcar una ausencia.
     */
    private function shouldMarkAsAbsent(array $attendance, CarbonInterface $date): bool
    {
        if (! $date->isToday()) {
            return true;
        }

        if (empty($attendance['scheduled_entry'])) {
            return false;
        }

        $scheduledStart = Carbon::parse($attendance['scheduled_entry'])->setDate($date->year, $date->month, $date->day);

        return $scheduledStart->diffInMinutes(now(), false) > 120;
    }

    /**
     * Registra un incidente si no existe uno previo para el mismo tipo y día.
     */
    private function recordIncident(
        EmployeeInterface $employee,
        string $typeCode,
        CarbonInterface $date,
        ?string $startTime,
        ?string $endTime,
        string $comment
    ): ?AttendanceIncident {
        $type = IncidentType::where('code', $typeCode)->first();
        if (! $type) {
            Log::warning("ReconcileAttendance: No se encontró el tipo de incidente {$typeCode}");

            return null;
        }

        // Verificar si ya existe un incidente similar para evitar spam
        $exists = AttendanceIncident::where('employee_id', $employee->getId())
            ->where('incident_type_id', $type->id)
            ->whereDate('incident_date', $date->toDateString())
            ->exists();

        if ($exists) {
            return null;
        }

        $incident = AttendanceIncident::create([
            'employee_id' => $employee->getId(),
            'incident_type_id' => $type->id,
            'incident_date' => $date->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'admin_comment' => $comment,
        ]);

        // Notificar al empleado
        $user = User::find($employee->getUserId());
        if ($user) {
            $dto = new NotificationDTO(
                title: 'Incidencia de Asistencia',
                message: "Se ha registrado una incidencia de tipo '{$type->name}' para el día {$date->format('d/m/Y')}. Motivo: {$comment}",
                actionUrl: route('schedules.my-schedule'),
                level: 'warning'
            );
            $user->notify(new AttendanceIncidentNotification($dto));
        }

        return $incident;
    }
}
