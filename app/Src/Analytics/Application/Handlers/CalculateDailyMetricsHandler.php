<?php

declare(strict_types=1);

namespace App\Src\Analytics\Application\Handlers;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\PersonnelModule\Models\Employee;
use App\Src\Analytics\Domain\Entities\AgentDailyMetric;
use App\Src\Analytics\Domain\Repositories\AnalyticsRepositoryInterface;
use App\Src\Analytics\Domain\Services\KpiCalculationService;
use DateTimeImmutable;

final class CalculateDailyMetricsHandler
{
    public function __construct(
        private AnalyticsRepositoryInterface $repository,
        private KpiCalculationService $kpi,
    ) {}

    public function handle(int $employeeId, DateTimeImmutable $date): AgentDailyMetric
    {
        $calls = AgentCallPerformance::where('employee_id', $employeeId)
            ->whereDate('start_time', $date->format('Y-m-d'))
            ->get();

        $callsTotal = $calls->count();
        $talkSeconds = (int) $calls->sum('talk_time');
        $workSeconds = (int) $calls->sum('work_time');

        $weightedAht = $callsTotal > 0 ? ($talkSeconds + $workSeconds) / $callsTotal : 0;

        $metric = AgentDailyMetric::create($employeeId, $date);

        $metric->updateMetrics([
            'callsTotal' => $callsTotal,
            'talkSeconds' => $talkSeconds,
            'productiveSeconds' => $talkSeconds,
            'weightedAht' => round($weightedAht, 2),
            'loginSeconds' => (int) $calls->sum('total_duration'),
        ]);

        $capacityCalls = $this->kpi->capacityCalls($metric->productiveSeconds(), $weightedAht);
        $metric->updateMetrics([
            'capacityCalls' => $capacityCalls,
            'capacityGap' => round(max(0, $capacityCalls - $callsTotal), 2),
            'availabilityPct' => $metric->loginSeconds() > 0
                ? round(($metric->productiveSeconds() / $metric->loginSeconds()) * 100, 2) : 0,
            'efficiencyPct' => $capacityCalls > 0
                ? round(($callsTotal / $capacityCalls) * 100, 2) : 0,
        ]);

        $metric->updateMetrics([
            'pwiPct' => $this->kpi->pwi($metric->availabilityPct(), $metric->efficiencyPct()),
        ]);

        return $this->repository->saveMetric($metric);
    }
}
