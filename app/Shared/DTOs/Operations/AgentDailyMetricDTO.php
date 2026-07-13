<?php

declare(strict_types=1);

namespace App\Shared\DTOs\Operations;

use Spatie\LaravelData\Data;

final readonly class AgentDailyMetricDTO extends Data
{
    public function __construct(
        public int $employee_id,
        public string $metric_date,
        public int $login_seconds,
        public int $productive_seconds,
        public int $calls_total,
        public int $talk_seconds,
        public float $weighted_aht,
        public float $capacity_calls,
        public float $capacity_gap,
        public float $work_units,
        public float $availability_pct,
        public float $efficiency_pct,
        public float $pwi_pct,
        public array $queue_distribution = [],
    ) {}
}
