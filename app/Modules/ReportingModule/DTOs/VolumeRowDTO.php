<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class VolumeRowDTO
{
    public function __construct(
        public string $queueName,
        public string $date,
        public int $received,
        public int $handled,
        public int $abandoned,
        public float $abandonmentRate,
        public ?float $aht = null,
        public ?float $asa = null,
        public ?int $maxWaitTime = null,
        public ?int $minWaitTime = null,
        public ?float $avgAbandonTime = null,
        public ?float $slaPercentage = null,
    ) {}
}
