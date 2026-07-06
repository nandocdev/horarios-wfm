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

    /**
     * Detecta inconsistencias de mallas horarias (turnos vs excepciones, actividades vs excepciones)
     * para una fecha específica. Retorna una lista de alertas textuales descriptivas.
     */
    public function detectScheduleConflicts(string $date): array
    {
        $conflicts = [];
        $carbonDate = Carbon::parse($date);
        $startOfDay = $carbonDate->copy()->startOfDay();
        $endOfDay = $carbonDate->copy()->endOfDay();
        
        // 1. Obtener excepciones del día
        $exceptions = ScheduleException::where('start_at', '<=', $endOfDay)
            ->where('end_at', '>=', $startOfDay)
            ->with('employee')
            ->get();
            
        foreach ($exceptions as $ex) {
            $employee = $ex->employee;
            if (!$employee) {
                continue;
            }
            
            // Ver si tiene turno asignado hoy
            $assignment = WeeklyScheduleAssignment::where('employee_id', $employee->id)
                ->where('day_of_week', $carbonDate->dayOfWeekIso)
                ->where('is_replaced', false)
                ->whereHas('weeklySchedule', function ($q) use ($date) {
                    $q->where('week_start_date', '<=', $date)
                      ->where('week_end_date', '>=', $date);
                })
                ->with('schedule')
                ->first();
                
            if ($assignment && $assignment->schedule && $assignment->start_time && $assignment->end_time) {
                // Verificar si hay solapamiento
                $exStart = $ex->start_at;
                $exEnd = $ex->end_at;
                
                $shiftStart = Carbon::parse($date . ' ' . $assignment->start_time);
                $shiftEnd = Carbon::parse($date . ' ' . $assignment->end_time);
                
                if ($exStart->lt($shiftEnd) && $exEnd->gt($shiftStart)) {
                    $conflicts[] = [
                        'type' => 'shift_exception_collision',
                        'employee_name' => $employee->full_name ?? $employee->name,
                        'message' => "Conflicto en {$employee->name}: Excepción programada ({$exStart->format('H:i')}-{$exEnd->format('H:i')}) se solapa con su turno ({$assignment->start_time}-{$assignment->end_time})."
                    ];
                }
            }
            
            // Ver si tiene actividades intradía que colisionen hoy
            $activities = IntradayActivity::where('employee_id', $employee->id)
                ->whereRaw('time_range && tstzrange(?, ?)', [$startOfDay->toIso8601String(), $endOfDay->toIso8601String()])
                ->get();
                
            foreach ($activities as $act) {
                $actStart = $act->getRangeStart();
                $actEnd = $act->getRangeEnd();
                
                if ($actStart && $actEnd) {
                    if ($actStart->lt($ex->end_at) && $actEnd->gt($ex->start_at)) {
                        $conflicts[] = [
                            'type' => 'activity_exception_collision',
                            'employee_name' => $employee->full_name ?? $employee->name,
                            'message' => "Conflicto en {$employee->name}: Actividad intradía ({$actStart->format('H:i')}-{$actEnd->format('H:i')}) se solapa con excepción ({$ex->start_at->format('H:i')}-{$ex->end_at->format('H:i')})."
                        ];
                    }
                }
            }
        }
        
        return $conflicts;
    }
}
