<?php

declare(strict_types=1);

namespace App\Src\Wfm\Domain\Entities;

final class Schedule
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $name,
        private readonly string $startTime,
        private readonly string $endTime,
        private readonly int $totalMinutes,
        private readonly int $breakMinutes = 0,
        private readonly int $lunchMinutes = 0,
        private readonly bool $isLunchPaid = true,
        private readonly bool $isBreakPaid = true,
        private readonly bool $isActive = true,
        private readonly array $allowedDays = [],
    ) {}

    public static function create(
        string $name,
        string $startTime,
        string $endTime,
        int $totalMinutes,
        int $breakMinutes = 0,
        int $lunchMinutes = 0,
        bool $isLunchPaid = true,
        bool $isBreakPaid = true,
        array $allowedDays = [],
    ): self {
        return new self(null, $name, $startTime, $endTime, $totalMinutes, $breakMinutes, $lunchMinutes, $isLunchPaid, $isBreakPaid, true, $allowedDays);
    }

    public function id(): ?int { return $this->id; }
    public function name(): string { return $this->name; }
    public function startTime(): string { return $this->startTime; }
    public function endTime(): string { return $this->endTime; }
    public function totalMinutes(): int { return $this->totalMinutes; }
    public function breakMinutes(): int { return $this->breakMinutes; }
    public function lunchMinutes(): int { return $this->lunchMinutes; }
    public function isLunchPaid(): bool { return $this->isLunchPaid; }
    public function isBreakPaid(): bool { return $this->isBreakPaid; }
    public function isActive(): bool { return $this->isActive; }
    public function allowedDays(): array { return $this->allowedDays; }
}
