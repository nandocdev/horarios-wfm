<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Actions;

use App\Modules\OperationsModule\DTOs\EmployeePerformanceDTO;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Employees\EmployeeInterface;
use App\Shared\Contracts\Operations\AgentPerformanceRepositoryInterface;
use App\Shared\DTOs\Operations\AgentStateTransitionDTO;
use App\Shared\Support\Metrics\MetricFormulas;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class GetEmployeePerformanceAction {
    public function __construct(
        private readonly AgentPerformanceRepositoryInterface $performanceRepo,
    ) {
    }

    public function execute(EmployeeInterface $employee, Carbon $date): EmployeePerformanceDTO {
        // 1. Obtener Programación
        $schedule = $this->getSchedule($employee, $date);

        // 2. Obtener Datos UCCX Normalizados
        $transitions = $this->getTransitions($employee, $date);
        $callRecords = $this->getRawCallRecords($employee, $date);

        if ($transitions->isEmpty() && $callRecords->isEmpty() && !$schedule) {
            return EmployeePerformanceDTO::empty($date->toDateString());
        }

        // 3. Calcular Minutos Programados Totales (Soporte Midnight Crossing)
        $scheduledMinutes = 0;
        if ($schedule?->start_time && $schedule?->end_time) {
            $start = Carbon::parse($schedule->start_time)->setDate($date->year, $date->month, $date->day);
            $end = Carbon::parse($schedule->end_time)->setDate($date->year, $date->month, $date->day);

            if ($end->lessThan($start)) {
                $end = $end->addDay();
            }

            $scheduledMinutes = (int) $start->diffInMinutes($end);
        }

        // 4. Calcular Asistencia y Adherencia de Pausas
        $exception = $this->getException($employee, $date);
        $attendance = $this->calculateAttendance($schedule, $transitions, $callRecords, $exception);

        // 5. Calcular Tiempos por Actividad y Reason
        $reasons = $this->calculateTimeByReason($transitions);
        $activities = $this->calculateTimeByActivity($transitions, $scheduledMinutes, $date, $attendance['actual_entry'], $schedule);

        // 6. Métricas de Productividad y Utilización
        $metrics = $this->calculateProductivity($transitions, $scheduledMinutes, $date, $schedule);
        $metrics['total_logout_minutes'] = $activities['Logout'] ?? 0;

        // 7. Volumen de Llamadas
        $queues = $this->getCallVolumeSummary($callRecords);

        return new EmployeePerformanceDTO(
            date: $date->toDateString(),
            attendance: $attendance,
            activities: $activities,
            reasons: $reasons,
            metrics: $metrics,
            queues: $queues
        );
    }

    private function getRawCallRecords(EmployeeInterface $employee, Carbon $date): Collection {
        return $this->performanceRepo->getCallRecords($employee->getId(), $date);
    }

    private function getSchedule(EmployeeInterface $employee, Carbon $date): ?WeeklyScheduleAssignment {
        $schedule = WeeklyScheduleAssignment::query()
            ->where('employee_id', $employee->getId())
            ->whereHas('weeklySchedule', function ($q) use ($date) {
                $q->whereDate('week_start_date', '<=', $date->toDateString())
                    ->whereDate('week_end_date', '>=', $date->toDateString());
            })
            ->where('day_of_week', $date->dayOfWeekIso)
            ->first();

        return $schedule;
    }

    private function getTransitions(EmployeeInterface $employee, Carbon $date): Collection {
        $transitions = $this->performanceRepo->getStateTransitions($employee->getId(), $date);

        // Si es el día de hoy, ajustamos la duración de la última transición (si está en curso)
        if ($date->isToday() && $transitions->isNotEmpty()) {
            $last = $transitions->last();
            if ($last->duration === 0) {
                $startTime = Carbon::parse($last->transition_time);
                // Solo calculamos si la transición es del presente (evitar negativos si el reloj del server difiere)
                $elapsed = max(0, now()->diffInSeconds($startTime));
                $transitions->pop();
                $transitions->push(new AgentStateTransitionDTO(
                    employee_id: $last->employee_id,
                    transition_time: $last->transition_time,
                    agent_state: $last->agent_state,
                    reason_code: $last->reason_code,
                    duration: $elapsed,
                ));
            }
        }

        return $transitions;
    }

    private function getException(EmployeeInterface $employee, Carbon $date): ?ScheduleException {
        return ScheduleException::query()
            ->with('reason')
            ->where('employee_id', $employee->getId())
            ->where(function ($q) use ($date) {
                $q->whereDate('start_at', '<=', $date->toDateString())
                    ->whereDate('end_at', '>=', $date->toDateString());
            })
            ->first();
    }

    private function calculateAttendance(?WeeklyScheduleAssignment $schedule, Collection $transitions, Collection $calls, ?ScheduleException $exception = null): array {
        // Entrada real: El primer estado que NO sea Logout y que tenga al menos 10 segundos de duración
        $firstValidTransition = $transitions->first(fn($t) => $t->agent_state !== 'Logout' && $t->duration > 10);
        $actualEntry = $firstValidTransition ? $firstValidTransition->transition_time : null;

        $scheduledEntry = $schedule?->start_time;
        $diff = 0;
        $status = 'present';

        if ($scheduledEntry && $actualEntry) {
            $actualEntryTime = Carbon::parse($actualEntry);
            $scheduledEntryTime = Carbon::parse($scheduledEntry);

            // Normalizar fecha de entrada programada a la fecha de la actividad
            $scheduledDateTime = (clone $actualEntryTime)->setTime($scheduledEntryTime->hour, $scheduledEntryTime->minute, $scheduledEntryTime->second);

            $status = MetricFormulas::checkLate($scheduledDateTime, $actualEntryTime) ? 'tardanza' : 'a_tiempo';
            $diff = (int) $scheduledDateTime->diffInMinutes($actualEntryTime, false);
        } elseif ($scheduledEntry && !$actualEntry) {
            $status = $exception ? 'excepción' : 'ausente';
        }

        return [
            'scheduled_entry' => $scheduledEntry ? Carbon::parse($scheduledEntry)->format('H:i:s') : null,
            'actual_entry' => $actualEntry ? Carbon::parse($actualEntry)->format('H:i:s') : null,
            'diff_minutes' => $diff,
            'status' => $status,
            'exception_reason' => $exception?->reason?->name ?? $exception?->remarks,
            'lunch' => $this->calculateStateAdherence($transitions, $schedule, 'lunch'),
            'break' => $this->calculateStateAdherence($transitions, $schedule, 'break'),
        ];
    }

    private function calculateStateAdherence(Collection $transitions, ?WeeklyScheduleAssignment $schedule, string $type): array {
        $keywords = $type === 'lunch' ? ['almuerzo', 'lunch', 'comida'] : ['break', 'descanso', 'pausa'];
        $scheduledDuration = $type === 'lunch' ? ($schedule?->lunch_minutes ?? 45) : ($schedule?->break_minutes ?? 15);

        // Sumar duración total de todos los tramos que coincidan con motivos conocidos
        $actualSeconds = $transitions->filter(function ($t) use ($keywords) {
            $reason = strtolower((string) $t->reason_code);
            foreach ($keywords as $kw) {
                if (str_contains($reason, $kw)) {
                    return true;
                }
            }

            return false;
        })->sum('duration');

        $match = $transitions->first(function ($t) use ($keywords) {
            $reason = strtolower((string) $t->reason_code);
            foreach ($keywords as $kw) {
                if (str_contains($reason, $kw)) {
                    return true;
                }
            }

            return false;
        });

        // Fallback dinámico por duración (Solo si no hay motivos detectados)
        if ($actualSeconds === 0) {
            $fallback = $transitions->filter(fn($t) => in_array($t->agent_state, ['Not Ready', 'Auxiliary']));
            if ($type === 'lunch') {
                // Almuerzo: Bloque continuo más largo entre 30 y 90 minutos
                $target = $fallback->filter(fn($t) => $t->duration >= 1800 && $t->duration <= 5400)
                    ->sortByDesc('duration')
                    ->first();
                if ($target) {
                    $actualSeconds = $target->duration;
                    $match = $target;
                }
            } else {
                // Break: Bloques entre 5 y 25 minutos
                $actualSeconds = $fallback->filter(fn($t) => $t->duration >= 300 && $t->duration <= 1500)->sum('duration');
                $match = $fallback->filter(fn($t) => $t->duration >= 300 && $t->duration <= 1500)->first();
            }
        }

        return [
            'actual_start' => $match ? Carbon::parse($match->transition_time)->format('H:i:s') : null,
            'actual_duration' => (int) round($actualSeconds / 60),
            'scheduled_duration' => $scheduledDuration,
        ];
    }

    private function calculateTimeByReason(Collection $transitions): array {
        return $transitions->filter(fn($t) => $t->agent_state === 'Not Ready')
            ->groupBy(fn($t) => $t->reason_code ?: '')
            ->map(fn($group) => [
                'minutes' => round($group->sum('duration') / 60, 1),
                'count' => $group->count(),
            ])
            ->toArray();
    }

    private function calculateTimeByActivity(Collection $transitions, int $scheduledMinutes, Carbon $date, ?string $actualEntry, ?WeeklyScheduleAssignment $schedule): array {
        $activities = $transitions->groupBy('agent_state')
            ->map(fn($group) => round($group->sum('duration') / 60, 1))
            ->toArray();

        $totalConnectedMinutes = round($transitions->sum('duration') / 60, 1);

        $isToday = $date->isToday();

        // Determinar si estamos dentro de la ventana de jornada hoy
        $isWithinSchedule = false;
        if ($isToday && $schedule?->start_time && $schedule?->end_time) {
            $now = now();
            $start = Carbon::parse($schedule->start_time)->setDate($date->year, $date->month, $date->day);
            $end = Carbon::parse($schedule->end_time)->setDate($date->year, $date->month, $date->day);
            if ($end->lessThan($start)) {
                $end = $end->addDay();
            }

            $isWithinSchedule = $now->between($start, $end);
        }

        if ($isToday && $actualEntry && $isWithinSchedule) {
            $now = now();
            $entry = Carbon::parse($actualEntry);

            // Logout hoy: (Ahora - inicio real) - Tiempo conectado
            $elapsedSinceEntry = $entry->diffInMinutes($now);

            $logoutMinutes = round($elapsedSinceEntry - $totalConnectedMinutes, 1);
            $activities['Logout'] = max(0, $logoutMinutes);
        } else {
            // Para días pasados o si ya terminó su jornada
            if ($scheduledMinutes > $totalConnectedMinutes) {
                $activities['Logout'] = round($scheduledMinutes - $totalConnectedMinutes, 1);
            } else {
                $activities['Logout'] = 0;
            }
        }

        return $activities;
    }

    private function calculateProductivity(Collection $transitions, int $scheduledMinutes, Carbon $date, ?WeeklyScheduleAssignment $schedule): array {
        $productiveStates = ['READY', 'RESERVED', 'TALKING', 'WORK', 'HOLD', 'OUTBOUND'];
        $totalConnectedSeconds = $transitions->sum('duration');
        $productiveSeconds = $transitions->filter(fn($t) => in_array(strtoupper(trim((string) $t->agent_state)), $productiveStates))
            ->sum('duration');

        $connectedMinutes = round($totalConnectedSeconds / 60, 1);
        $productiveMinutes = round($productiveSeconds / 60, 1);

        $start = null;
        $end = null;
        if ($schedule?->start_time && $schedule?->end_time) {
            $start = Carbon::parse($schedule->start_time)->setDate($date->year, $date->month, $date->day);
            $end = Carbon::parse($schedule->end_time)->setDate($date->year, $date->month, $date->day);
            if ($end->lessThan($start)) {
                $end = $end->addDay();
            }
        }

        $denominator = MetricFormulas::utilizationDenominator(
            $scheduledMinutes,
            $date->isToday(),
            $start,
            $end
        );

        return [
            'total_scheduled_minutes' => $scheduledMinutes,
            'total_productive_minutes' => $productiveMinutes,
            'total_connected_minutes' => $connectedMinutes,
            'productivity_percentage' => MetricFormulas::productivity((float) $productiveSeconds / 60, $connectedMinutes),
            'utilization_percentage' => MetricFormulas::utilization($productiveMinutes, $denominator),
        ];
    }

    private function getCallVolumeSummary(Collection $calls): array {
        return $calls->groupBy('csq_name')
            ->map(fn($group) => [
                'total_calls' => $group->count(),
                'avg_handle_time' => MetricFormulas::aht(
                    (float) $group->sum('talk_time'),
                    (float) $group->sum('work_time'),
                    $group->count()
                ),
            ])
            ->toArray();
    }
}
