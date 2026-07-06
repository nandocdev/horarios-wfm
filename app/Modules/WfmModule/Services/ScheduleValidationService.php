<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Services;

use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class ScheduleValidationService
{
    /**
     * Valida que una hora de inicio sea anterior a la hora de fin.
     */
    public function validateTimes(string $startTime, string $endTime): bool
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        return $end->gt($start);
    }

    /**
     * Verifica si existe una colisión con turnos principales asignados para el mismo día y empleado.
     */
    public function hasWeeklyAssignmentOverlap(
        int $employeeId,
        int $weeklyScheduleId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $ignoreAssignmentId = null
    ): bool {
        $query = WeeklyScheduleAssignment::where('weekly_schedule_id', $weeklyScheduleId)
            ->where('employee_id', $employeeId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_replaced', false);

        if ($ignoreAssignmentId) {
            $query->where('id', '!=', $ignoreAssignmentId);
        }

        $existing = $query->first();

        if (! $existing || ! $existing->start_time || ! $existing->end_time) {
            return false;
        }

        // Validar solapamiento matemático de las horas
        $newStart = Carbon::parse($startTime);
        $newEnd = Carbon::parse($endTime);

        $existStart = Carbon::parse($existing->start_time);
        $existEnd = Carbon::parse($existing->end_time);

        return $newStart->lt($existEnd) && $newEnd->gt($existStart);
    }

    /**
     * Verifica si existe una colisión con actividades intradía (breaks, reuniones, almuerzos)
     * registradas para el empleado en la fecha y rango especificados.
     * Utiliza la comparación de rangos nativa de PostgreSQL (TSTZRANGE).
     */
    public function hasIntradayActivityCollision(
        int $employeeId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreActivityId = null
    ): bool {
        $startTz = Carbon::parse($date . ' ' . $startTime)->toIso8601String();
        $endTz = Carbon::parse($date . ' ' . $endTime)->toIso8601String();

        $query = IntradayActivity::where('employee_id', $employeeId);

        if ($ignoreActivityId) {
            $query->where('id', '!=', $ignoreActivityId);
        }

        if (DB::getDriverName() === 'pgsql') {
            // Comparación nativa de solapamiento de rangos de tiempo (&&) en PostgreSQL
            return $query->whereRaw('time_range && tstzrange(?, ?)', [$startTz, $endTz])->exists();
        }

        // Fallback básico para testing en base de datos no-PostgreSQL
        return false;
    }

    /**
     * Verifica si existe solapamiento con excepciones programadas (vacaciones, citas, permisos).
     */
    public function hasExceptionOverlap(
        int $employeeId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreExceptionId = null
    ): bool {
        $start = Carbon::parse($date . ' ' . $startTime);
        $end = Carbon::parse($date . ' ' . $endTime);

        $query = ScheduleException::where('employee_id', $employeeId)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_at', '<', $end)
                  ->where('end_at', '>', $start);
            });

        if ($ignoreExceptionId) {
            $query->where('id', '!=', $ignoreExceptionId);
        }

        return $query->exists();
    }
}
