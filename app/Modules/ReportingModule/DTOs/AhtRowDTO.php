<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class AhtRowDTO
{
    public function __construct(
        public string $agentName,
        public string $queueName,
        public string $date,
        public int $callsHandled,
        public float $avgTalkTime,
        public float $avgWorkTime,
        public float $avgHoldTime,
        public float $aht,
        public ?int $ahtGoal = null,
        public ?float $deviation = null,
        public ?float $minAht = null,
        public ?float $maxAht = null,
    ) {}
}
