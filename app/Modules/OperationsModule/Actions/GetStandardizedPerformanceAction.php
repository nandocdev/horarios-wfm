<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Actions;

use App\Modules\OperationsModule\DTOs\StandardizedPerformanceDTO;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Shared\Support\Metrics\MetricFormulas;
use App\Shared\Contracts\Schedules\ScheduleServiceInterface;
use App\Shared\Contracts\Telemetry\TelemetryServiceInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Acción para calcular el desempeño estandarizado de un empleado.
 * DESACOPLADO: Utiliza contratos para obtener datos de Plan (WFM) y Realidad (Connect).
 */
final class GetStandardizedPerformanceAction
{
    public function __construct(
        private readonly ScheduleServiceInterface $scheduleService,
        private readonly TelemetryServiceInterface $telemetryService
    ) {}

    public function execute(Employee $employee, CarbonInterface $date): StandardizedPerformanceDTO
    {
        $schedule = $this->scheduleService->getScheduleForEmployee($employee->id, $date);
        $transitions = $this->telemetryService->getStateTransitions($employee->id, $date->copy()->startOfDay(), $date->copy()->endOfDay());
        $intradayActivities = $this->getIntradayActivities($employee->id, $date);
        $callRecords = $this->getCallRecords($employee->id, $date);
        
        if ($transitions->isEmpty() && $callRecords->isEmpty() && $schedule->is_off) {
            return StandardizedPerformanceDTO::empty($date->toDateString());
        }

        $scheduledMinutes = 0;
        if ($schedule->start_time && $schedule->end_time) {
            $start = Carbon::parse($schedule->start_time)->setDate($date->year, $date->month, $date->day);
            $end = Carbon::parse($schedule->end_time)->setDate($date->year, $date->month, $date->day);
            if ($end->lessThan($start)) $end->addDay();
            $scheduledMinutes = (int) $start->diffInMinutes($end);
        }

        $attendance = $this->calculateAttendance($schedule, $transitions);
        $reasons = $this->calculateTimeByReason($transitions);
        $activities = $this->calculateTimeByActivity($transitions, $scheduledMinutes, $date, $attendance['actual_entry'], $schedule);
        $metrics = $this->calculateProductivity($transitions, $intradayActivities, $scheduledMinutes, $date, $schedule);
        $metrics['total_logout_minutes'] = $activities['Logout'] ?? 0;
        
        $queues = $this->getCallVolumeSummary($callRecords);

        return new StandardizedPerformanceDTO(
            date: $date->toDateString(),
            attendance: $attendance,
            activities: $activities,
            reasons: $reasons,
            metrics: $metrics,
            queues: $queues
        );
    }

    private function getIntradayActivities(int $employeeId, CarbonInterface $date): Collection
    {
        return IntradayActivity::query()
            ->with('activityType')
            ->where('employee_id', $employeeId)
            ->whereRaw('lower(time_range)::date = ?', [$date->toDateString()])
            ->get();
    }

    private function getCallRecords(int $employeeId, CarbonInterface $date): Collection
    {
        return AgentCallPerformance::query()
            ->where('employee_id', $employeeId)
            ->whereDate('start_time', $date->toDateString())
            ->get();
    }

    private function calculateAttendance($schedule, Collection $transitions): array
    {
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

    private function calculateStateAdherence(Collection $transitions, $schedule, string $type): array
    {
        $keywords = $type === 'lunch' ? ['almuerzo', 'lunch', 'comida'] : ['break', 'descanso', 'pausa'];
        $scheduledDuration = $type === 'lunch' ? $schedule->lunch_minutes : $schedule->break_minutes;

        $actualSeconds = $transitions->filter(function ($t) use ($keywords) {
            $reason = strtolower((string)$t->reason_code);
            foreach ($keywords as $kw) {
                if (str_contains($reason, $kw)) return true;
            }
            return false;
        })->sum(fn($t) => $t->metadata['duration'] ?? 0);

        $match = $transitions->filter(function ($t) use ($keywords) {
            $reason = strtolower((string)$t->reason_code);
            foreach ($keywords as $kw) {
                if (str_contains($reason, $kw)) return true;
            }
            return false;
        })->first();

        return [
            'actual_start' => $match ? Carbon::parse($match->last_changed_at)->format('H:i:s') : null,
            'actual_duration' => (int) round($actualSeconds / 60),
            'scheduled_duration' => $scheduledDuration,
        ];
    }

    private function calculateTimeByReason(Collection $transitions): array
    {
        return $transitions->filter(fn($t) => $t->current_state === 'Not Ready')
            ->groupBy(fn($t) => $t->reason_code ?: '')
            ->map(fn($group) => [
                'minutes' => round($group->sum(fn($t) => $t->metadata['duration'] ?? 0) / 60, 1),
                'count'   => $group->count(),
            ])
            ->toArray();
    }

    private function calculateTimeByActivity(Collection $transitions, int $scheduledMinutes, CarbonInterface $date, ?string $actualEntry, $schedule): array
    {
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
                if ($end->lessThan($start)) $end->addDay();
                $shiftDuration = $start->diffInMinutes($end);
                $activities['Logout'] = $totalConnectedMinutes < $shiftDuration ? round($shiftDuration - $totalConnectedMinutes, 1) : 0;
            } else {
                $activities['Logout'] = 0;
            }
        }
        return $activities;
    }

    private function calculateProductivity(Collection $transitions, Collection $intradayActivities, int $scheduledMinutes, CarbonInterface $date, $schedule): array
    {
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
            if ($end->lessThan($start)) $end->addDay();
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
            'productivity_percentage' => MetricFormulas::productivity($systemProductiveSeconds / 60, $connectedMinutes),
            'utilization_percentage' => MetricFormulas::utilization($productiveMinutes, $denominator)
        ];
    }

    private function getCallVolumeSummary(Collection $calls): array
    {
        return $calls->groupBy('csq_name')
            ->map(fn($group) => [
                'total_calls' => $group->count(),
                'avg_handle_time' => MetricFormulas::aht(
                    (float) $group->sum('talk_time'),
                    (float) $group->sum('work_time'),
                    $group->count()
                )
            ])
            ->toArray();
    }
}
