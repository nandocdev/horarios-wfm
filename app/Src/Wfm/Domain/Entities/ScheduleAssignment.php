<?php

declare(strict_types=1);

namespace App\Src\Wfm\Domain\Entities;

use DateTimeImmutable;

final class ScheduleAssignment
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $weeklyScheduleId,
        private readonly int $employeeId,
        private readonly int $dayOfWeek,
        private readonly ?DateTimeImmutable $startTime,
        private readonly ?DateTimeImmutable $endTime,
        private readonly ?DateTimeImmutable $lunchStartTime = null,
        private readonly ?DateTimeImmutable $lunchEndTime = null,
        private readonly ?DateTimeImmutable $breakStartTime = null,
        private readonly ?DateTimeImmutable $breakEndTime = null,
        private readonly int $scheduleId = 0,
        private readonly bool $isReplaced = false,
    ) {}

    public static function create(
        int $weeklyScheduleId,
        int $employeeId,
        int $dayOfWeek,
        ?DateTimeImmutable $startTime,
        ?DateTimeImmutable $endTime,
        ?DateTimeImmutable $lunchStartTime = null,
        ?DateTimeImmutable $lunchEndTime = null,
        ?DateTimeImmutable $breakStartTime = null,
        ?DateTimeImmutable $breakEndTime = null,
        int $scheduleId = 0,
    ): self {
        return new self(
            null, $weeklyScheduleId, $employeeId, $dayOfWeek,
            $startTime, $endTime,
            $lunchStartTime, $lunchEndTime,
            $breakStartTime, $breakEndTime,
            $scheduleId, false,
        );
    }

    public function id(): ?int { return $this->id; }
    public function weeklyScheduleId(): int { return $this->weeklyScheduleId; }
    public function employeeId(): int { return $this->employeeId; }
    public function dayOfWeek(): int { return $this->dayOfWeek; }
    public function startTime(): ?DateTimeImmutable { return $this->startTime; }
    public function endTime(): ?DateTimeImmutable { return $this->endTime; }
    public function lunchStartTime(): ?DateTimeImmutable { return $this->lunchStartTime; }
    public function lunchEndTime(): ?DateTimeImmutable { return $this->lunchEndTime; }
    public function breakStartTime(): ?DateTimeImmutable { return $this->breakStartTime; }
    public function breakEndTime(): ?DateTimeImmutable { return $this->breakEndTime; }
    public function scheduleId(): int { return $this->scheduleId; }
    public function isReplaced(): bool { return $this->isReplaced; }
}
