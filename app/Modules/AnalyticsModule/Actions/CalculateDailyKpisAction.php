<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Actions;

use App\Modules\AnalyticsModule\Models\DailyKpi;
use App\Modules\AnalyticsModule\Models\ForecastAccuracy;
use App\Modules\AnalyticsModule\Models\HistoricalShrinkage;
use App\Modules\OperationsModule\Models\AgentDailyMetric;
use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\QualityModule\Models\Evaluation;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CalculateDailyKpisAction
{
    public function execute(CarbonInterface $date): array
    {
        $dateStr = $date->toDateString();
        $result = ['employees' => 0, 'teams' => 0, 'global' => 0];

        DB::transaction(function () use ($date, $dateStr, &$result) {
            $employees = Employee::where('is_active', true)->get();

            $employeeKpis = collect();

            foreach ($employees as $employee) {
                $kpi = $this->calculateEmployeeKpi($employee->id, $employee->team_id, $date);
                if ($kpi !== null) {
                    $employeeKpis->push($kpi);
                    $result['employees']++;
                }
            }

            $teamKpis = $this->aggregateByTeam($employeeKpis, $dateStr);
            foreach ($teamKpis as $kpi) {
                $result['teams']++;
            }

            $globalKpi = $this->calculateGlobal($employeeKpis, $dateStr);
            if ($globalKpi) {
                $result['global'] = 1;
            }
        });

        return $result;
    }

    private function calculateEmployeeKpi(int $employeeId, ?int $teamId, CarbonInterface $date): ?DailyKpi
    {
        $dateStr = $date->toDateString();

        $intervalMetrics = AgentIntervalMetric::where('employee_id', $employeeId)
            ->whereDate('interval_start', $dateStr)
            ->get();

        $dailyMetric = AgentDailyMetric::where('employee_id', $employeeId)
            ->whereDate('metric_date', $dateStr)
            ->first();

        if ($intervalMetrics->isEmpty() && ! $dailyMetric) {
            return null;
        }

        $totalTalk = (int) $intervalMetrics->sum('talk_seconds');
        $totalHold = (int) $intervalMetrics->sum('hold_seconds');
        $totalWrap = (int) $intervalMetrics->sum('wrap_seconds');
        $totalReady = (int) $intervalMetrics->sum('ready_seconds');
        $totalNotReady = (int) $intervalMetrics->sum('not_ready_seconds');
        $totalCalls = (int) $intervalMetrics->sum('calls_handled');
        $totalLogged = $totalTalk + $totalHold + $totalWrap + $totalReady + $totalNotReady;

        $avgOccupancy = $intervalMetrics->avg('occupancy') ?? 0;
        $avgUtilization = $intervalMetrics->avg('utilization') ?? 0;
        $avgAdherence = $intervalMetrics->avg('adherence') ?? 0;

        $productiveSeconds = $totalTalk + $totalHold + $totalWrap;
        $ahtSeconds = $totalCalls > 0
            ? round($productiveSeconds / $totalCalls, 2)
            : 0;

        $productivity = $totalLogged > 0
            ? round(($productiveSeconds / $totalLogged) * 100, 2)
            : 0;

        $acwSeconds = $totalCalls > 0
            ? round($totalWrap / $totalCalls, 2)
            : 0;

        $scheduledMinutes = $dailyMetric?->login_seconds
            ? (int) round($dailyMetric->login_seconds / 60)
            : 0;

        $conformance = $scheduledMinutes > 0
            ? round((($totalLogged / 60) / $scheduledMinutes) * 100, 2)
            : 0;

        $shrinkageMinutes = (int) HistoricalShrinkage::where('employee_id', $employeeId)
            ->whereDate('date', $dateStr)
            ->sum('duration_minutes');

        $shrinkagePct = $scheduledMinutes > 0
            ? round(($shrinkageMinutes / $scheduledMinutes) * 100, 2)
            : 0;

        $qualityScore = Evaluation::where('employee_id', $employeeId)
            ->whereDate('dteval', $dateStr)
            ->whereNotNull('score')
            ->avg('score');

        $forecastAccuracy = ForecastAccuracy::where('evaluation_date', $dateStr)
            ->avg('accuracy');

        $loginSeconds = $dailyMetric?->login_seconds ?? $totalLogged;

        return DailyKpi::updateOrCreate(
            [
                'evaluation_date' => $dateStr,
                'granularity' => 'employee',
                'dim_employee_id' => $employeeId,
            ],
            [
                'dim_team_id' => $teamId,
                'occupancy' => round($avgOccupancy, 2),
                'utilization' => round($avgUtilization, 2),
                'productivity' => $productivity,
                'conformance' => $conformance,
                'adherence' => round($avgAdherence, 2),
                'aht_seconds' => $ahtSeconds,
                'acw_seconds' => $acwSeconds,
                'shrinkage_pct' => $shrinkagePct,
                'forecast_accuracy_pct' => $forecastAccuracy ? round((float) $forecastAccuracy, 2) : null,
                'quality_score' => $qualityScore ? round((float) $qualityScore, 2) : null,
                'total_calls' => $totalCalls,
                'total_talk_seconds' => $totalTalk,
                'total_hold_seconds' => $totalHold,
                'total_wrap_seconds' => $totalWrap,
                'total_ready_seconds' => $totalReady,
                'total_not_ready_seconds' => $totalNotReady,
                'total_login_seconds' => $loginSeconds,
                'total_scheduled_minutes' => $scheduledMinutes,
            ],
        );
    }

    private function aggregateByTeam(Collection $employeeKpis, string $dateStr): array
    {
        $byTeam = $employeeKpis->groupBy('dim_team_id')->filter(fn ($items, $teamId) => $teamId !== null);
        $created = [];

        foreach ($byTeam as $teamId => $kpis) {
            $avgOccupancy = $kpis->avg('occupancy');
            $avgUtilization = $kpis->avg('utilization');
            $avgProductivity = $kpis->avg('productivity');
            $avgConformance = $kpis->avg('conformance');
            $avgAdherence = $kpis->avg('adherence');
            $avgAht = $kpis->avg('aht_seconds');
            $avgAcw = $kpis->avg('acw_seconds');
            $avgShrinkage = $kpis->avg('shrinkage_pct');
            $avgQuality = $kpis->avg('quality_score');

            DailyKpi::updateOrCreate(
                [
                    'evaluation_date' => $dateStr,
                    'granularity' => 'team',
                    'dim_team_id' => $teamId,
                ],
                [
                    'occupancy' => $avgOccupancy ? round((float) $avgOccupancy, 2) : null,
                    'utilization' => $avgUtilization ? round((float) $avgUtilization, 2) : null,
                    'productivity' => $avgProductivity ? round((float) $avgProductivity, 2) : null,
                    'conformance' => $avgConformance ? round((float) $avgConformance, 2) : null,
                    'adherence' => $avgAdherence ? round((float) $avgAdherence, 2) : null,
                    'aht_seconds' => $avgAht ? round((float) $avgAht, 2) : null,
                    'acw_seconds' => $avgAcw ? round((float) $avgAcw, 2) : null,
                    'shrinkage_pct' => $avgShrinkage ? round((float) $avgShrinkage, 2) : null,
                    'quality_score' => $avgQuality ? round((float) $avgQuality, 2) : null,
                    'total_calls' => $kpis->sum('total_calls'),
                    'total_talk_seconds' => $kpis->sum('total_talk_seconds'),
                    'total_hold_seconds' => $kpis->sum('total_hold_seconds'),
                    'total_wrap_seconds' => $kpis->sum('total_wrap_seconds'),
                    'total_ready_seconds' => $kpis->sum('total_ready_seconds'),
                    'total_not_ready_seconds' => $kpis->sum('total_not_ready_seconds'),
                    'total_login_seconds' => $kpis->sum('total_login_seconds'),
                    'total_scheduled_minutes' => $kpis->sum('total_scheduled_minutes'),
                ],
            );

            $created[] = $teamId;
        }

        return $created;
    }

    private function calculateGlobal(Collection $employeeKpis, string $dateStr): ?DailyKpi
    {
        if ($employeeKpis->isEmpty()) {
            return null;
        }

        $avgOccupancy = $employeeKpis->avg('occupancy');
        $avgUtilization = $employeeKpis->avg('utilization');
        $avgProductivity = $employeeKpis->avg('productivity');
        $avgConformance = $employeeKpis->avg('conformance');
        $avgAdherence = $employeeKpis->avg('adherence');
        $avgAht = $employeeKpis->avg('aht_seconds');
        $avgAcw = $employeeKpis->avg('acw_seconds');
        $avgShrinkage = $employeeKpis->avg('shrinkage_pct');
        $avgQuality = $employeeKpis->avg('quality_score');

        $forecastAccuracy = ForecastAccuracy::where('evaluation_date', $dateStr)
            ->avg('accuracy');

        return DailyKpi::updateOrCreate(
            [
                'evaluation_date' => $dateStr,
                'granularity' => 'global',
            ],
            [
                'occupancy' => $avgOccupancy ? round((float) $avgOccupancy, 2) : null,
                'utilization' => $avgUtilization ? round((float) $avgUtilization, 2) : null,
                'productivity' => $avgProductivity ? round((float) $avgProductivity, 2) : null,
                'conformance' => $avgConformance ? round((float) $avgConformance, 2) : null,
                'adherence' => $avgAdherence ? round((float) $avgAdherence, 2) : null,
                'aht_seconds' => $avgAht ? round((float) $avgAht, 2) : null,
                'acw_seconds' => $avgAcw ? round((float) $avgAcw, 2) : null,
                'shrinkage_pct' => $avgShrinkage ? round((float) $avgShrinkage, 2) : null,
                'quality_score' => $avgQuality ? round((float) $avgQuality, 2) : null,
                'forecast_accuracy_pct' => $forecastAccuracy ? round((float) $forecastAccuracy, 2) : null,
                'total_calls' => $employeeKpis->sum('total_calls'),
                'total_talk_seconds' => $employeeKpis->sum('total_talk_seconds'),
                'total_hold_seconds' => $employeeKpis->sum('total_hold_seconds'),
                'total_wrap_seconds' => $employeeKpis->sum('total_wrap_seconds'),
                'total_ready_seconds' => $employeeKpis->sum('total_ready_seconds'),
                'total_not_ready_seconds' => $employeeKpis->sum('total_not_ready_seconds'),
                'total_login_seconds' => $employeeKpis->sum('total_login_seconds'),
                'total_scheduled_minutes' => $employeeKpis->sum('total_scheduled_minutes'),
            ],
        );
    }
}
