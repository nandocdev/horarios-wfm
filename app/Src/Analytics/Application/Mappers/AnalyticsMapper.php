<?php

declare(strict_types=1);

namespace App\Src\Analytics\Application\Mappers;

use App\Src\Analytics\Domain\Entities\AgentDailyMetric;
use App\Src\Analytics\Infrastructure\Persistence\EloquentAgentDailyMetric;
use DateTimeImmutable;

final class AnalyticsMapper
{
    public static function toDomain(EloquentAgentDailyMetric $e): AgentDailyMetric
    {
        return new AgentDailyMetric(
            id: $e->id,
            employeeId: $e->employee_id,
            metricDate: self::toImmutable($e->metric_date),
            loginSeconds: (int) ($e->login_seconds ?? 0),
            productiveSeconds: (int) ($e->productive_seconds ?? 0),
            callsTotal: (int) ($e->calls_total ?? 0),
            talkSeconds: (int) ($e->talk_seconds ?? 0),
            weightedAht: (float) ($e->weighted_aht ?? 0),
            capacityCalls: (float) ($e->capacity_calls ?? 0),
            capacityGap: (float) ($e->capacity_gap ?? 0),
            workUnits: (float) ($e->work_units ?? 0),
            availabilityPct: (float) ($e->availability_pct ?? 0),
            efficiencyPct: (float) ($e->efficiency_pct ?? 0),
            pwiPct: (float) ($e->pwi_pct ?? 0),
            queueDistribution: $e->queue_distribution ?? [],
            adherencePct: $e->adherence_pct !== null ? (float) $e->adherence_pct : null,
            productivityPct: $e->productivity_pct !== null ? (float) $e->productivity_pct : null,
            utilizationPct: $e->utilization_pct !== null ? (float) $e->utilization_pct : null,
        );
    }

    private static function toImmutable(mixed $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) return $date;
        if ($date instanceof \DateTime) return DateTimeImmutable::createFromMutable($date);
        return new DateTimeImmutable((string) $date);
    }
}
