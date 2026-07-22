<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class VolumeIntervalRowDTO
{
    public function __construct(
        public string $queueName,
        public string $interval,
        public int $offered,
        public int $handled,
        public int $abandoned,
        public float $abandonmentRate,
        public ?float $aht = null,
        public ?float $asa = null,
        public ?int $maxWaitTime = null,
    ) {}
}
