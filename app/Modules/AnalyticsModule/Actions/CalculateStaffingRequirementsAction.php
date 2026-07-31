<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Actions;

use App\Modules\AnalyticsModule\Models\ForecastInterval;
use App\Modules\AnalyticsModule\Models\ForecastScenario;
use App\Modules\AnalyticsModule\Models\StaffingRequirement;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Support\Metrics\CapacityMetrics;
use App\Shared\Support\Metrics\SchedulingMetrics;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class CalculateStaffingRequirementsAction
{
    private const SECONDS_PER_INTERVAL = 900;

    public function execute(
        string $forecastScenarioId,
        CarbonInterface $date,
        float $shrinkageRate = 0.0,
    ): Collection {
        $scenario = ForecastScenario::with(['version.group'])->findOrFail($forecastScenarioId);
        $queueId = $scenario->version->group->reference_id ?? 'general';

        $intervals = ForecastInterval::where('forecast_scenario_id', $forecastScenarioId)
            ->whereDate('interval_start', $date->toDateString())
            ->orderBy('interval_start')
            ->get();

        if ($intervals->isEmpty()) {
            return collect();
        }

        $dayOfWeek = (int) $date->format('N');
        $todayStr = $date->toDateString();
        $scheduledAssignments = $this->loadScheduledAssignments($date, $dayOfWeek);

        $results = collect();

        foreach ($intervals as $interval) {
            $intervalStart = $interval->interval_start;
            $intervalEnd = $interval->interval_end;
            $intervalMinutes = $interval->interval_minutes ?? 15;

            $scheduledCount = $this->countScheduledInInterval(
                $scheduledAssignments,
                $intervalStart,
                $intervalEnd,
            );

            $requiredAgents = CapacityMetrics::offeredLoad(
                $interval->call_volume_forecast,
                $interval->aht_seconds_forecast,
                $intervalMinutes * 60,
            );

            $availableAgents = round($scheduledCount * (1 - ($shrinkageRate / 100)), 2);
            $coverage = SchedulingMetrics::coverage($availableAgents, $requiredAgents);
            $gap = round($requiredAgents - $availableAgents, 2);

            $requirement = StaffingRequirement::updateOrCreate(
                [
                    'interval_start' => $intervalStart,
                    'queue_id' => $queueId,
                ],
                [
                    'interval_end' => $intervalEnd,
                    'interval_minutes' => $intervalMinutes,
                    'required_agents' => $requiredAgents,
                    'scheduled_agents' => $scheduledCount,
                    'available_agents' => $availableAgents,
                    'coverage' => $coverage,
                    'gap' => $gap,
                    'shrinkage_rate' => $shrinkageRate,
                    'forecast_version_id' => $scenario->forecast_version_id,
                ],
            );

            $results->push($requirement);
        }

        return $results;
    }

    private function loadScheduledAssignments(CarbonInterface $date, int $dayOfWeek): Collection
    {
        $weekStart = $date->copy()->startOfWeek(CarbonInterface::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(CarbonInterface::SUNDAY);

        $weeklySchedule = WeeklySchedule::where('week_start_date', $weekStart->toDateString())
            ->where('status', 'published')
            ->first();

        if (! $weeklySchedule) {
            return collect();
        }

        return WeeklyScheduleAssignment::where('weekly_schedule_id', $weeklySchedule->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_replaced', false)
            ->get(['employee_id', 'start_time', 'end_time']);
    }

    private function countScheduledInInterval(
        Collection $assignments,
        CarbonInterface $intervalStart,
        CarbonInterface $intervalEnd,
    ): int {
        $startTime = $intervalStart->format('H:i:s');
        $endTime = $intervalEnd->format('H:i:s');

        return $assignments->filter(function ($assignment) use ($startTime, $endTime) {
            $scheduledStart = $assignment->start_time?->format('H:i:s');
            $scheduledEnd = $assignment->end_time?->format('H:i:s');

            if (! $scheduledStart || ! $scheduledEnd) {
                return false;
            }

            return $scheduledStart < $endTime && $scheduledEnd > $startTime;
        })->count();
    }
}
