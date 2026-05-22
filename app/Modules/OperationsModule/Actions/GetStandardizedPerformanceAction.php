<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Actions;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\OperationsModule\DTOs\StandardizedPerformanceDTO;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Shared\Contracts\Schedules\ScheduleServiceInterface;
use App\Shared\Contracts\Telemetry\TelemetryServiceInterface;
use App\Shared\DTOs\Telemetry\TelemetryStateDTO;
use App\Shared\Support\Metrics\MetricFormulas;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Acción para calcular el desempeño estandarizado de un empleado.
 * DESACOPLADO: Utiliza contratos para obtener datos de Plan (WFM) y Realidad (Connect).
 */
final class GetStandardizedPerformanceAction {
    public function __construct(
        private readonly ScheduleServiceInterface $scheduleService,
        private readonly TelemetryServiceInterface $telemetryService,
        private readonly CalculateRealAdherenceAction $adherenceAction
    ) {
    }

    public function execute(Employee $employee, CarbonInterface $date): StandardizedPerformanceDTO {
        $schedule = $this->scheduleService->getScheduleForEmployee($employee->id, $date);
        $transitions = $this->telemetryService->getStateTransitions($employee->id, $date->copy()->startOfDay(), $date->copy()->endOfDay());

        // Si es el día de hoy, ajustamos la duración de la última transición (si está en curso)
        if ($date->isToday() && $transitions->isNotEmpty()) {
            $last = $transitions->last();
            if ((int) ($last->metadata['duration'] ?? 0) === 0) {
                $startTime = Carbon::parse($last->last_changed_at);
                $elapsed = max(0, now()->diffInSeconds($startTime));

                // Reemplazamos el DTO por uno nuevo con la duración actualizada (ya que es readonly)
                $updatedMetadata = $last->metadata;
                $updatedMetadata['duration'] = $elapsed;

                $updatedLast = new TelemetryStateDTO(
                    $last->employee_id,
                    $last->current_state,
                    $last->reason_code,
                    $last->last_changed_at,
                    $updatedMetadata
                );

                // Actualizar en la colección
                $transitions->pop();
                $transitions->push($updatedLast);
            }
        }

        $intradayActivities = $this->getIntradayActivities($employee->id, $date);
        $callRecords = $this->getCallRecords($employee->id, $date);

        if ($transitions->isEmpty() && $callRecords->isEmpty() && $schedule->is_off) {
            return StandardizedPerformanceDTO::empty($date->toDateString());
        }

        $scheduledMinutes = 0;
        if ($schedule->start_time && $schedule->end_time) {
            $start = Carbon::parse($schedule->start_time)->setDate($date->year, $date->month, $date->day);
            $end = Carbon::parse($schedule->end_time)->setDate($date->year, $date->month, $date->day);
            if ($end->lessThan($start)) {
                $end->addDay();
            }
            $scheduledMinutes = (int) $start->diffInMinutes($end);
        }

        $attendance = $this->calculateAttendance($schedule, $transitions);
        $reasons = $this->calculateTimeByReason($transitions);
        $activities = $this->calculateTimeByActivity($transitions, $scheduledMinutes, $date, $attendance['actual_entry'], $schedule);
        $metrics = $this->calculateProductivity($employee, $transitions, $intradayActivities, $scheduledMinutes, $date, $schedule);
        $metrics['total_logout_minutes'] = $activities['Logout'] ?? 0;

        $logoutDetails = $this->calculateLogoutDetails($transitions, $schedule, $date);

        $queues = $this->getCallVolumeSummary($callRecords);

        // Obtener metas de KPIs configuradas
        $goals = \Illuminate\Support\Facades\DB::table('operational_settings')
            ->where('category', 'kpi_goal')
            ->pluck('value', 'key')
            ->toArray();

        return new StandardizedPerformanceDTO(
            date: $date->toDateString(),
            attendance: $attendance,
            activities: $activities,
            reasons: $reasons,
            metrics: $metrics,
            queues: $queues,
            goals: $goals,
            logout_details: $logoutDetails
        );
    }

    private function getIntradayActivities(int $employeeId, CarbonInterface $date): Collection {
        return IntradayActivity::query()
            ->with('activityType')
            ->where('employee_id', $employeeId)
            ->whereRaw('lower(time_range)::date = ?', [$date->toDateString()])
            ->get();
    }

    /**
     * Obtiene los registros de llamadas de un empleado en un rango de fechas.
     * 
     * @param int $employeeId
     * @param \Carbon\CarbonInterface $date
     * @return Collection<int, \stdClass>|\Illuminate\Database\Eloquent\Collection<int, AgentCallPerformance>
     */
    private function getCallRecords(int $employeeId, CarbonInterface $date): Collection {
        return AgentCallPerformance::query()
            ->where('employee_id', $employeeId)
            ->whereDate('start_time', $date->toDateString())
            ->get();
    }

    /**
     * Obtiene los registros de llamadas de los empleados de un team en un rango de fecha
     */
    public function getTeamCallRecords(array $teamIds, CarbonInterface $startDate, CarbonInterface $endDate): Collection
    {
        return AgentCallPerformance::query()
            ->join('employees', 'agent_call_performances.employee_id', '=', 'employees.id')
            ->whereIn('employees.team_id', $teamIds)
            ->whereBetween('start_time', [$startDate->toDateString(), $endDate->toDateString()])
            ->select('agent_call_performances.*')
            ->get();
    }



    private function calculateAttendance($schedule, Collection $transitions): array {
        $firstValidTransition = $transitions->first(fn($t) => $t->current_state !== 'Logout' && ($t->metadata['duration'] ?? 0) > 10);
        $actualEntry = $firstValidTransition ? $firstValidTransition->last_changed_at : null;

        $scheduledEntry = $schedule->start_time;
        $diff = 0;
        $status = 'present';

        if ($scheduledEntry && $actualEntry) {
            $status = MetricFormulas::checkLate($scheduledEntry, $actualEntry) ? 'tardanza' : 'a_tiempo';

            $actualEntryTime = Carbon::parse($actualEntry);
            $scheduledEntryTime = Carbon::parse($scheduledEntry);
            $scheduledDateTime = (clone $actualEntryTime)->setTime($scheduledEntryTime->hour, $scheduledEntryTime->minute, $scheduledEntryTime->second);

            $diff = (int) $scheduledDateTime->diffInMinutes($actualEntryTime, false);
        } elseif ($scheduledEntry && !$actualEntry) {
            $status = !empty($schedule->exceptions) ? 'excepción' : 'ausente';
        }

        return [
            'scheduled_entry' => $scheduledEntry,
            'actual_entry' => $actualEntry ? Carbon::parse($actualEntry)->format('H:i:s') : null,
            'diff_minutes' => $diff,
            'status' => $status,
            'exception_reason' => $schedule->exceptions[0]['type'] ?? null,
            'lunch' => $this->calculateStateAdherence($transitions, $schedule, 'lunch'),
            'break' => $this->calculateStateAdherence($transitions, $schedule, 'break'),
        ];
    }

    private function calculateStateAdherence(Collection $transitions, $schedule, string $type): array {
        // Refinamos las keywords para evitar colisiones (ej. 'pausa' atrapaba 'biopausa')
        $keywords = $type === 'lunch'
            ? ['almuerzo', 'lunch', 'comida']
            : ['break', 'descanso'];

        $scheduledDuration = $type === 'lunch' ? $schedule->lunch_minutes : $schedule->break_minutes;
        $scheduledStart = $type === 'lunch' ? $schedule->lunch_start_time : $schedule->break_start_time;

        // Solo sumamos si el estado es Not Ready (Auxiliar) para consistencia
        $actualSeconds = $transitions->filter(function ($t) use ($keywords) {
            if ($t->current_state !== 'Not Ready') {
                return false;
            }

            $reason = trim(strtolower((string) $t->reason_code));
            foreach ($keywords as $kw) {
                if (str_contains($reason, $kw)) {
                    return true;
                }
            }

            return false;
        })->sum(fn($t) => $t->metadata['duration'] ?? 0);

        $match = $transitions->filter(function ($t) use ($keywords) {
            if ($t->current_state !== 'Not Ready') {
                return false;
            }

            $reason = trim(strtolower((string) $t->reason_code));
            foreach ($keywords as $kw) {
                if (str_contains($reason, $kw)) {
                    return true;
                }
            }

            return false;
        })->first();

        $actualStart = $match ? Carbon::parse($match->last_changed_at) : null;
        $diff = 0;

        if ($scheduledStart && $actualStart) {
            $scheduledStartTime = Carbon::parse($scheduledStart);
            $scheduledDateTime = (clone $actualStart)->setTime($scheduledStartTime->hour, $scheduledStartTime->minute, $scheduledStartTime->second);
            $diff = (int) $scheduledDateTime->diffInMinutes($actualStart, false);
        }

        return [
            'scheduled_start' => $scheduledStart ? Carbon::parse($scheduledStart)->format('H:i:s') : null,
            'actual_start' => $actualStart ? $actualStart->format('H:i:s') : null,
            'diff_minutes' => $diff,
            'actual_duration' => round($actualSeconds / 60, 1),
            'scheduled_duration' => $scheduledDuration,
        ];
    }

    private function calculateTimeByReason(Collection $transitions): array {
        return $transitions->filter(fn($t) => $t->current_state === 'Not Ready')
            ->groupBy(fn($t) => $t->reason_code ?: '')
            ->map(fn($group) => [
                'minutes' => round($group->sum(fn($t) => $t->metadata['duration'] ?? 0) / 60, 1),
                'count' => $group->count(),
            ])
            ->toArray();
    }

    private function calculateTimeByActivity(Collection $transitions, int $scheduledMinutes, CarbonInterface $date, ?string $actualEntry, $schedule): array {
        $activities = $transitions->groupBy('current_state')
            ->map(fn($group) => round($group->sum(fn($t) => $t->metadata['duration'] ?? 0) / 60, 1))
            ->toArray();

        $totalConnectedMinutes = round($transitions->sum(fn($t) => $t->metadata['duration'] ?? 0) / 60, 1);

        if ($date->isToday() && $actualEntry) {
            $now = now();
            $entry = Carbon::parse($actualEntry);
            $elapsedSinceEntry = $entry->diffInMinutes($now);
            $activities['Logout'] = max(0, round($elapsedSinceEntry - $totalConnectedMinutes, 1));
        } else {
            if ($schedule->start_time && $schedule->end_time) {
                $start = Carbon::parse($schedule->start_time)->setDate($date->year, $date->month, $date->day);
                $end = Carbon::parse($schedule->end_time)->setDate($date->year, $date->month, $date->day);
                if ($end->lessThan($start)) {
                    $end->addDay();
                }
                $shiftDuration = $start->diffInMinutes($end);
                $activities['Logout'] = $totalConnectedMinutes < $shiftDuration ? round($shiftDuration - $totalConnectedMinutes, 1) : 0;
            } else {
                $activities['Logout'] = 0;
            }
        }

        return $activities;
    }

    private function calculateProductivity(Employee $employee, Collection $transitions, Collection $intradayActivities, int $scheduledMinutes, CarbonInterface $date, $schedule): array {
        $systemProductiveSeconds = $transitions->filter(fn($t) => $t->metadata['is_productive'] ?? false)->sum(fn($t) => $t->metadata['duration'] ?? 0);
        $intradayProductiveMinutes = $intradayActivities->filter(fn($a) => $a->activityType?->is_productive)
            ->sum(fn($a) => $a->getRangeStart() && $a->getRangeEnd() ? $a->getRangeStart()->diffInMinutes($a->getRangeEnd()) : 0);

        $productiveMinutes = round(($systemProductiveSeconds / 60) + $intradayProductiveMinutes, 1);

        $totalConnectedSeconds = $transitions->sum(fn($t) => $t->metadata['duration'] ?? 0);
        $connectedMinutes = round($totalConnectedSeconds / 60, 1);

        $start = null;
        $end = null;
        if ($schedule->start_time && $schedule->end_time) {
            $start = Carbon::parse($schedule->start_time)->setDate($date->year, $date->month, $date->day);
            $end = Carbon::parse($schedule->end_time)->setDate($date->year, $date->month, $date->day);
            if ($end->lessThan($start)) {
                $end->addDay();
            }
        }

        $denominator = MetricFormulas::utilizationDenominator(
            $scheduledMinutes,
            $date->isToday(),
            $start,
            $end
        );

        // --- CÁLCULO DE ADHERENCIA REAL ---
        $adherenceRes = $this->adherenceAction->execute($employee, $date);
        $adherencePercentage = $adherenceRes['percentage'];

        return [
            'total_scheduled_minutes' => $scheduledMinutes,
            'total_productive_minutes' => $productiveMinutes,
            'total_connected_minutes' => $connectedMinutes,
            'productivity_percentage' => MetricFormulas::productivity($systemProductiveSeconds / 60, $connectedMinutes),
            'utilization_percentage' => MetricFormulas::utilization($productiveMinutes, $denominator),
            'adherence_percentage' => $adherencePercentage,
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

    /**
     * Calcula los detalles de desconexión (Logout) identificando los huecos entre actividades.
     */
    private function calculateLogoutDetails(Collection $transitions, $schedule, CarbonInterface $date): array
    {
        $details = [];
        $count = $transitions->count();

        if ($count < 2) {
            return [];
        }

        for ($i = 0; $i < $count - 1; $i++) {
            $current = $transitions->get($i);
            $next = $transitions->get($i + 1);

            $currentEnd = Carbon::parse($current->last_changed_at)->addSeconds((int)($current->metadata['duration'] ?? 0));
            $nextStart = Carbon::parse($next->last_changed_at);

            // Si hay un hueco mayor a 10 segundos entre actividades, lo consideramos un Logout/Desconexión
            $gapSeconds = $nextStart->diffInSeconds($currentEnd, false);
            if ($gapSeconds < -10) { // nextStart is after currentEnd (diffInSeconds with false returns negative if $this < $target)
                $absGap = abs($gapSeconds);
                $details[] = [
                    'start_time' => $currentEnd->format('H:i:s'),
                    'end_time' => $nextStart->format('H:i:s'),
                    'duration_minutes' => round($absGap / 60, 1),
                ];
            }
        }

        return $details;
    }
}
