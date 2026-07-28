<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Actions;

use App\Modules\AnalyticsModule\Models\CapacityPlan;
use App\Modules\AnalyticsModule\Models\ForecastInterval;
use App\Modules\AnalyticsModule\Models\ForecastScenario;
use App\Modules\OperationsModule\Models\QueueSkill;
use App\Modules\PersonnelModule\Models\EmployeeSkill;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class GenerateCapacityPlanAction
{
    public function execute(
        string $forecastScenarioId,
        CarbonInterface $planDate,
        float $shrinkageRate = 0.0,
        ?int $generatedBy = null,
        ?string $name = null,
    ): CapacityPlan {
        $scenario = ForecastScenario::with(['version.group'])->findOrFail($forecastScenarioId);
        $queueId = $scenario->version->group->reference_id ?? 'general';

        $forecastIntervals = ForecastInterval::where('forecast_scenario_id', $forecastScenarioId)
            ->whereDate('interval_start', $planDate->toDateString())
            ->orderBy('interval_start')
            ->get();

        if ($forecastIntervals->isEmpty()) {
            throw new \RuntimeException('No forecast intervals found for scenario '.$forecastScenarioId.' on '.$planDate->toDateString());
        }

        $dayOfWeek = (int) $planDate->format('N');
        $todayStr = $planDate->toDateString();
        $intervalMinutes = $forecastIntervals->first()->interval_minutes ?? 15;

        $scheduledAssignments = $this->loadScheduledAssignments($planDate, $dayOfWeek);
        $requiredSkillIds = $this->getRequiredSkillIdsForQueue($queueId);
        $employeesWithSkill = $this->getEmployeesWithSkill($requiredSkillIds);

        $planName = $name ?? 'Capacity Plan - '.$queueId.' - '.$planDate->toDateString();

        return DB::transaction(function () use (
            $forecastIntervals, $planDate, $shrinkageRate, $generatedBy,
            $planName, $queueId, $scenario, $scheduledAssignments,
            $employeesWithSkill, $intervalMinutes,
        ) {
            $plan = CapacityPlan::create([
                'name' => $planName,
                'description' => 'Generado desde forecast '.$scenario->version->name,
                'status' => 'draft',
                'plan_date' => $planDate->toDateString(),
                'generated_by' => $generatedBy,
                'generated_at' => CarbonImmutable::now(),
                'forecast_version_id' => $scenario->forecast_version_id,
                'shrinkage_rate' => $shrinkageRate,
            ]);

            $intervalBatch = [];
            $queueTotals = [];

            foreach ($forecastIntervals as $fi) {
                $callVolume = $fi->call_volume_forecast;
                $aht = $fi->aht_seconds_forecast;
                $staffRequired = $fi->staff_required > 0
                    ? $fi->staff_required
                    : $this->calculateRequired($callVolume, $aht, $intervalMinutes);

                $staffScheduled = $this->countScheduledInInterval(
                    $scheduledAssignments,
                    $fi->interval_start,
                    $fi->interval_end,
                );

                $staffWithSkill = $this->countScheduledWithSkill(
                    $scheduledAssignments,
                    $employeesWithSkill,
                    $fi->interval_start,
                    $fi->interval_end,
                );

                $staffAvailable = round($staffScheduled * (1 - ($shrinkageRate / 100)), 2);
                $coverage = $staffRequired > 0
                    ? round(($staffAvailable / $staffRequired) * 100, 2)
                    : 0.0;
                $gap = round($staffRequired - $staffAvailable, 2);
                $skillGap = round(max(0, $staffRequired - $staffWithSkill), 2);

                $intervalBatch[] = [
                    'capacity_plan_id' => $plan->id,
                    'interval_start' => $fi->interval_start,
                    'interval_end' => $fi->interval_end,
                    'interval_minutes' => $intervalMinutes,
                    'queue_id' => $queueId,
                    'forecast_call_volume' => $callVolume,
                    'forecast_aht' => $aht,
                    'staff_required' => $staffRequired,
                    'staff_scheduled' => $staffScheduled,
                    'staff_available' => $staffAvailable,
                    'staff_with_skill' => $staffWithSkill,
                    'coverage' => $coverage,
                    'gap' => $gap,
                    'skill_gap' => $skillGap,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (! isset($queueTotals[$queueId])) {
                    $queueTotals[$queueId] = [
                        'total_intervals' => 0,
                        'intervals_with_gap' => 0,
                        'intervals_with_skill_gap' => 0,
                        'max_gap' => 0,
                        'coverage_sum' => 0,
                        'total_staff_required' => 0,
                        'total_staff_available' => 0,
                    ];
                }

                $t = &$queueTotals[$queueId];
                $t['total_intervals']++;
                $t['coverage_sum'] += $coverage;
                $t['total_staff_required'] += $staffRequired;
                $t['total_staff_available'] += $staffAvailable;
                if ($gap > 0) {
                    $t['intervals_with_gap']++;
                }
                if ($skillGap > 0) {
                    $t['intervals_with_skill_gap']++;
                }
                if ($gap > $t['max_gap']) {
                    $t['max_gap'] = $gap;
                }
            }

            DB::table('capacity_intervals')->insert($intervalBatch);

            $resultBatch = [];
            foreach ($queueTotals as $qId => $totals) {
                $resultBatch[] = [
                    'capacity_plan_id' => $plan->id,
                    'queue_id' => $qId,
                    'total_intervals' => $totals['total_intervals'],
                    'intervals_with_gap' => $totals['intervals_with_gap'],
                    'intervals_with_skill_gap' => $totals['intervals_with_skill_gap'],
                    'max_gap' => $totals['max_gap'],
                    'avg_coverage' => $totals['total_intervals'] > 0
                        ? round($totals['coverage_sum'] / $totals['total_intervals'], 2)
                        : 0.0,
                    'total_staff_required' => round($totals['total_staff_required'], 2),
                    'total_staff_available' => round($totals['total_staff_available'], 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('capacity_results')->insert($resultBatch);

            return $plan->load(['intervals', 'results']);
        });
    }

    private function loadScheduledAssignments(CarbonInterface $date, int $dayOfWeek): Collection
    {
        $weekStart = $date->copy()->startOfWeek(CarbonInterface::MONDAY);
        $weeklySchedule = WeeklySchedule::where('week_start_date', $weekStart->toDateString())
            ->where('status', 'published')
            ->first();

        if (! $weeklySchedule) {
            return collect();
        }

        return WeeklyScheduleAssignment::with('employee.employeeSkills')
            ->where('weekly_schedule_id', $weeklySchedule->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_replaced', false)
            ->get();
    }

    private function getRequiredSkillIdsForQueue(string $queueId): array
    {
        return QueueSkill::whereHas('queue', function ($q) use ($queueId) {
            $q->where('name', $queueId);
        })
            ->where('is_required', true)
            ->pluck('skill_id')
            ->toArray();
    }

    private function getEmployeesWithSkill(array $skillIds): Collection
    {
        if (empty($skillIds)) {
            return collect();
        }

        return EmployeeSkill::whereIn('skill_id', $skillIds)
            ->where('is_active', true)
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($skills) => $skills->pluck('skill_id')->toArray());
    }

    private function countScheduledInInterval(
        Collection $assignments,
        CarbonInterface $intervalStart,
        CarbonInterface $intervalEnd,
    ): int {
        $startTime = $intervalStart->format('H:i:s');
        $endTime = $intervalEnd->format('H:i:s');

        return $assignments->filter(function ($a) use ($startTime, $endTime) {
            $sStart = $a->start_time?->format('H:i:s');
            $sEnd = $a->end_time?->format('H:i:s');

            if (! $sStart || ! $sEnd) {
                return false;
            }

            return $sStart < $endTime && $sEnd > $startTime;
        })->count();
    }

    private function countScheduledWithSkill(
        Collection $assignments,
        Collection $employeeSkills,
        CarbonInterface $intervalStart,
        CarbonInterface $intervalEnd,
    ): int {
        if ($employeeSkills->isEmpty()) {
            return $this->countScheduledInInterval($assignments, $intervalStart, $intervalEnd);
        }

        $startTime = $intervalStart->format('H:i:s');
        $endTime = $intervalEnd->format('H:i:s');

        return $assignments->filter(function ($a) use ($startTime, $endTime, $employeeSkills) {
            $sStart = $a->start_time?->format('H:i:s');
            $sEnd = $a->end_time?->format('H:i:s');

            if (! $sStart || ! $sEnd) {
                return false;
            }

            $inInterval = $sStart < $endTime && $sEnd > $startTime;
            if (! $inInterval) {
                return false;
            }

            $employeeSkillIds = $employeeSkills->get($a->employee_id);
            if ($employeeSkillIds === null) {
                return false;
            }

            return $employeeSkillIds->isNotEmpty();
        })->count();
    }

    private function calculateRequired(int $callVolume, float $aht, int $intervalMinutes): float
    {
        if ($callVolume <= 0 || $aht <= 0) {
            return 0.0;
        }

        return round(($callVolume * $aht) / ($intervalMinutes * 60), 2);
    }
}
