<?php

declare(strict_types=1);

namespace App\Src\Wfm\Domain\Entities;

use DateTimeImmutable;

final class IntradayActivity
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $employeeId,
        private readonly int $activityTypeId,
        private readonly DateTimeImmutable $startTime,
        private readonly DateTimeImmutable $endTime,
        private readonly ?int $approvedPeriodId = null,
        private readonly ?string $notes = null,
    ) {}

    public static function create(
        int $employeeId,
        int $activityTypeId,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime,
        ?int $approvedPeriodId = null,
        ?string $notes = null,
    ): self {
        return new self(null, $employeeId, $activityTypeId, $startTime, $endTime, $approvedPeriodId, $notes);
    }

    public function id(): ?int { return $this->id; }
    public function employeeId(): int { return $this->employeeId; }
    public function activityTypeId(): int { return $this->activityTypeId; }
    public function startTime(): DateTimeImmutable { return $this->startTime; }
    public function endTime(): DateTimeImmutable { return $this->endTime; }
    public function approvedPeriodId(): ?int { return $this->approvedPeriodId; }
    public function notes(): ?string { return $this->notes; }

    public function overlapsWith(IntradayActivity $other): bool
    {
        return $this->employeeId === $other->employeeId
            && $this->startTime < $other->endTime
            && $this->endTime > $other->startTime;
    }
}
