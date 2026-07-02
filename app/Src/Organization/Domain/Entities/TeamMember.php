<?php

declare(strict_types=1);

namespace App\Src\Organization\Domain\Entities;

use DateTimeImmutable;

final class TeamMember
{
    private ?int $id;
    private int $teamId;
    private int $employeeId;
    private DateTimeImmutable $joinedAt;
    private ?DateTimeImmutable $leftAt;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        ?int $id,
        int $teamId,
        int $employeeId,
        DateTimeImmutable $joinedAt,
        ?DateTimeImmutable $leftAt = null,
        bool $isActive = true,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->teamId = $teamId;
        $this->employeeId = $employeeId;
        $this->joinedAt = $joinedAt;
        $this->leftAt = $leftAt;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public static function assign(int $teamId, int $employeeId, ?DateTimeImmutable $joinedAt = null): self
    {
        return new self(null, $teamId, $employeeId, $joinedAt ?? new DateTimeImmutable());
    }

    public function id(): ?int { return $this->id; }
    public function teamId(): int { return $this->teamId; }
    public function employeeId(): int { return $this->employeeId; }
    public function joinedAt(): DateTimeImmutable { return $this->joinedAt; }
    public function leftAt(): ?DateTimeImmutable { return $this->leftAt; }
    public function isActive(): bool { return $this->isActive; }

    public function remove(?DateTimeImmutable $leftAt = null): void
    {
        $this->leftAt = $leftAt ?? new DateTimeImmutable();
        $this->isActive = false;
    }
}
