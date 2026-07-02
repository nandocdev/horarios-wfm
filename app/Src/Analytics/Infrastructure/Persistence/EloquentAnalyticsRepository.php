<?php

declare(strict_types=1);

namespace App\Src\Analytics\Infrastructure\Persistence;

use App\Src\Analytics\Application\Mappers\AnalyticsMapper;
use App\Src\Analytics\Domain\Entities\AgentDailyMetric;
use App\Src\Analytics\Domain\Repositories\AnalyticsRepositoryInterface;
use DateTimeImmutable;

final class EloquentAnalyticsRepository implements AnalyticsRepositoryInterface
{
    public function saveMetric(AgentDailyMetric $metric): AgentDailyMetric
    {
        $eloquent = EloquentAgentDailyMetric::updateOrCreate(
            ['employee_id' => $metric->employeeId(), 'metric_date' => $metric->metricDate()->format('Y-m-d')],
            [
                'login_seconds' => $metric->loginSeconds(),
                'productive_seconds' => $metric->productiveSeconds(),
                'calls_total' => $metric->callsTotal(),
                'talk_seconds' => $metric->talkSeconds(),
                'weighted_aht' => $metric->weightedAht(),
                'capacity_calls' => $metric->capacityCalls(),
                'capacity_gap' => $metric->capacityGap(),
                'work_units' => $metric->workUnits(),
                'availability_pct' => $metric->availabilityPct(),
                'efficiency_pct' => $metric->efficiencyPct(),
                'pwi_pct' => $metric->pwiPct(),
                'queue_distribution' => $metric->queueDistribution(),
                'adherence_pct' => $metric->adherencePct(),
                'productivity_pct' => $metric->productivityPct(),
                'utilization_pct' => $metric->utilizationPct(),
            ],
        );

        return AnalyticsMapper::toDomain($eloquent);
    }

    public function findMetricByEmployeeAndDate(int $employeeId, DateTimeImmutable $date): ?AgentDailyMetric
    {
        $eloquent = EloquentAgentDailyMetric::where('employee_id', $employeeId)
            ->whereDate('metric_date', $date->format('Y-m-d'))
            ->first();

        return $eloquent ? AnalyticsMapper::toDomain($eloquent) : null;
    }

    public function aggregateByTeam(int $teamId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return EloquentAgentDailyMetric::whereBetween('metric_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereHas('employee', fn ($q) => $q->where('team_id', $teamId))
            ->selectRaw('
                AVG(availability_pct) as avg_availability,
                AVG(efficiency_pct) as avg_efficiency,
                AVG(pwi_pct) as avg_pwi,
                SUM(calls_total) as total_calls,
                AVG(adherence_pct) as avg_adherence
            ')
            ->first()
            ?->toArray() ?? [];
    }

    public function getLatestMetricsByEmployee(array $employeeIds): array
    {
        return EloquentAgentDailyMetric::whereIn('employee_id', $employeeIds)
            ->latest('metric_date')
            ->get()
            ->keyBy('employee_id')
            ->map(fn (EloquentAgentDailyMetric $e) => AnalyticsMapper::toDomain($e))
            ->toArray();
    }
}
