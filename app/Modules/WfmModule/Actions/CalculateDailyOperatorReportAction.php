<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\DailyOperatorReport;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalculateDailyOperatorReportAction
{
    public function __construct(
        private TelemetryRealtimeRepositoryInterface $realtimeRepo,
        private DashboardScheduleQueriesInterface $scheduleQueries,
    ) {}

    /**
     * Calcula y guarda el reporte diario para un empleado en una fecha.
     */
    public function execute(int $employeeId, string $date): DailyOperatorReport
    {
        $now = Carbon::parse($date);
        $today = $now->toDateString();
        $dayOfWeek = $now->dayOfWeekIso;
        $employee = Employee::with('team', 'position')->find($employeeId);

        if (! $employee) {
            throw new \RuntimeException("Employee {$employeeId} no encontrado.");
        }

        // 1. Schedule snapshot
        $assignment = WeeklyScheduleAssignment::with('schedule')
            ->where('employee_id', $employeeId)
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('weeklySchedule', fn ($q) => $q
                ->where('week_start_date', '<=', $today)
                ->where('week_end_date', '>=', $today)
            )
            ->first();

        $scheduledStart = $assignment?->start_time;
        $scheduledEnd = $assignment?->end_time;
        $lunchStart = $assignment?->lunch_start_time;
        $lunchEnd = $assignment?->lunch_end_time;
        $breakStart = $assignment?->break_start_time;
        $breakEnd = $assignment?->break_end_time;

        // 2. Transitions for time breakdown
        $transitions = $this->realtimeRepo->getBatchStateTransitions([$employeeId], $today);
        $timeByState = $transitions->groupBy(fn ($t) => strtoupper(trim($t->agent_state)))
            ->map(fn ($group) => $group->sum('duration'));

        $talkSeconds = $timeByState->get('TALKING', 0);
        $readySeconds = $timeByState->get('READY', 0);
        $acwSeconds = $timeByState->get('WORK', 0) + $timeByState->get('ACW', 0);
        $reservedSeconds = $timeByState->get('RESERVED', 0);
        $notReadySeconds = $timeByState->get('NOT_READY', 0);
        $lunchSeconds = $timeByState->get('NOT_READY_LUNCH', 0) + $timeByState->get('NOT_READY_ALMUERZO', 0) + $timeByState->get('LUNCH', 0);
        $breakSeconds = $timeByState->get('NOT_READY_BREAK', 0) + $timeByState->get('NOT_READY_DESCANSO', 0) + $timeByState->get('BREAK', 0);
        $offlineSeconds = $timeByState->get('LOGOUT', 0) + $timeByState->get('OFFLINE', 0);

        $productiveSeconds = $talkSeconds + $readySeconds + $acwSeconds + $reservedSeconds;
        $totalSeconds = $timeByState->sum();
        $effectiveSeconds = $productiveSeconds + $readySeconds;

        // 3. Real entry
        $firstTransition = $transitions->sortBy('transition_time')->first();
        $realEntry = $firstTransition?->transition_time
            ? Carbon::parse($firstTransition->transition_time)
            : null;

        $entryDiffMinutes = null;
        if ($realEntry && $scheduledStart) {
            $sched = Carbon::parse($scheduledStart);
            $entryDiffMinutes = (int) $sched->diffInMinutes($realEntry, false);
        }

        // 4. Call stats
        $callStats = $this->realtimeRepo->getCallStatsForDate($today, [$employeeId]);
        $totalCalls = (int) ($callStats->total ?? 0);
        $handledCalls = (int) ($callStats->handled ?? 0);
        $abandonedCalls = max(0, $totalCalls - $handledCalls);

        // 5. Exceptions
        $exceptionCount = $this->scheduleQueries->getExceptionCount([$employeeId], $today);

        // 6. Calculate KPIs
        $occupancyPct = $effectiveSeconds > 0
            ? round(($productiveSeconds / $effectiveSeconds) * 100, 2)
            : null;

        $productivityPct = $totalSeconds > 0
            ? round(($productiveSeconds / $totalSeconds) * 100, 2)
            : null;

        $avgHandleTime = $handledCalls > 0
            ? round(($talkSeconds + $acwSeconds) / $handledCalls, 2)
            : null;

        return DailyOperatorReport::updateOrCreate(
            ['employee_id' => $employeeId, 'report_date' => $today],
            [
                'scheduled_start' => $scheduledStart,
                'scheduled_end' => $scheduledEnd,
                'lunch_start' => $lunchStart,
                'lunch_end' => $lunchEnd,
                'break_start' => $breakStart,
                'break_end' => $breakEnd,
                'talk_seconds' => $talkSeconds,
                'ready_seconds' => $readySeconds,
                'acw_seconds' => $acwSeconds,
                'reserved_seconds' => $reservedSeconds,
                'not_ready_seconds' => $notReadySeconds,
                'lunch_seconds' => $lunchSeconds,
                'break_seconds' => $breakSeconds,
                'offline_seconds' => $offlineSeconds,
                'total_calls' => $totalCalls,
                'handled_calls' => $handledCalls,
                'abandoned_calls' => $abandonedCalls,
                'total_talk_seconds' => $talkSeconds,
                'total_hold_seconds' => 0,
                'total_work_seconds' => $acwSeconds,
                'adherence_pct' => null,
                'occupancy_pct' => $occupancyPct,
                'productivity_pct' => $productivityPct,
                'avg_handle_time' => $avgHandleTime,
                'exception_count' => $exceptionCount,
                'has_exceptions' => $exceptionCount > 0,
                'real_entry' => $realEntry,
                'entry_diff_minutes' => $entryDiffMinutes,
                'is_complete' => true,
            ]
        );
    }

    /**
     * Calcula reportes para todos los empleados activos en una fecha.
     */
    public function executeAll(string $date): array
    {
        $employees = Employee::where('is_active', true)
            ->whereNotNull('username')
            ->get(['id']);

        $results = ['success' => 0, 'error' => 0];

        foreach ($employees as $employee) {
            try {
                $this->execute((int) $employee->id, $date);
                $results['success']++;
            } catch (\Exception $e) {
                Log::error("Error calculando reporte diario para empleado {$employee->id}: {$e->getMessage()}");
                $results['error']++;
            }
        }

        return $results;
    }
}
