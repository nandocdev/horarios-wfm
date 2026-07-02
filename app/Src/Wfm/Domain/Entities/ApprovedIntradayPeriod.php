<?php

declare(strict_types=1);

namespace App\Src\Wfm\Domain\Entities;

use DateTimeImmutable;

final class ApprovedIntradayPeriod
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $teamId,
        private readonly int $activityDefinitionId,
        private readonly DateTimeImmutable $date,
        private readonly string $startTime,
        private readonly string $endTime,
        private readonly int $maxSlots,
        private readonly ?string $notes = null,
        private readonly int $usedSlots = 0,
    ) {}

    public static function create(
        int $teamId,
        int $activityDefinitionId,
        DateTimeImmutable $date,
        string $startTime,
        string $endTime,
        int $maxSlots,
        ?string $notes = null,
    ): self {
        return new self(null, $teamId, $activityDefinitionId, $date, $startTime, $endTime, $maxSlots, $notes);
    }

    public function id(): ?int { return $this->id; }
    public function teamId(): int { return $this->teamId; }
    public function activityDefinitionId(): int { return $this->activityDefinitionId; }
    public function date(): DateTimeImmutable { return $this->date; }
    public function startTime(): string { return $this->startTime; }
    public function endTime(): string { return $this->endTime; }
    public function maxSlots(): int { return $this->maxSlots; }
    public function notes(): ?string { return $this->notes; }
    public function usedSlots(): int { return $this->usedSlots; }

    public function availableSlots(): int { return max(0, $this->maxSlots - $this->usedSlots); }
    public function isFull(): bool { return $this->usedSlots >= $this->maxSlots; }
}
