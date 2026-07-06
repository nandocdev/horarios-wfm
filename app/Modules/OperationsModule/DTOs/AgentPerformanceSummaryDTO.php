<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\DTOs;

final readonly class AgentPerformanceSummaryDTO
{
    public function __construct(
        public array $days,
        public array $summary,
        public array $stateDistribution,
        public array $queueDetail,
        public array $deviations,
    ) {}

    public static function empty(): self
    {
        return new self(
            days: [],
            summary: [
                'avg_adherence' => 0,
                'avg_occupancy' => 0,
                'total_calls' => 0,
                'avg_aht_seconds' => 0,
                'total_aux_minutes' => 0,
            ],
            stateDistribution: [],
            queueDetail: [],
            deviations: [],
        );
    }
}
