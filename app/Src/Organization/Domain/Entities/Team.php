<?php

declare(strict_types=1);

namespace App\Src\Organization\Domain\Entities;

final class Team
{
    private ?int $id;
    private string $name;
    private ?string $description;
    private ?int $supervisorId;
    private bool $isActive;
    private ?int $baseScheduleId;
    private ?string $ciscoTeamId;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
    private array $memberIds;

    public function __construct(
        ?int $id,
        string $name,
        ?string $description = null,
        ?int $supervisorId = null,
        bool $isActive = true,
        ?int $baseScheduleId = null,
        ?string $ciscoTeamId = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
        array $memberIds = [],
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->supervisorId = $supervisorId;
        $this->isActive = $isActive;
        $this->baseScheduleId = $baseScheduleId;
        $this->ciscoTeamId = $ciscoTeamId;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
        $this->memberIds = $memberIds;
    }

    public static function create(
        string $name,
        ?string $description = null,
        ?int $supervisorId = null,
        ?int $baseScheduleId = null,
        ?string $ciscoTeamId = null,
    ): self {
        return new self(null, $name, $description, $supervisorId, true, $baseScheduleId, $ciscoTeamId);
    }

    public function id(): ?int { return $this->id; }
    public function name(): string { return $this->name; }
    public function description(): ?string { return $this->description; }
    public function supervisorId(): ?int { return $this->supervisorId; }
    public function isActive(): bool { return $this->isActive; }
    public function baseScheduleId(): ?int { return $this->baseScheduleId; }
    public function ciscoTeamId(): ?string { return $this->ciscoTeamId; }
    public function memberIds(): array { return $this->memberIds; }
    public function memberCount(): int { return count($this->memberIds); }

    public function rename(string $name): void { $this->name = $name; }
    public function updateDescription(?string $description): void { $this->description = $description; }
    public function assignSupervisor(?int $employeeId): void { $this->supervisorId = $employeeId; }
    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }
    public function updateCiscoTeamId(?string $ciscoTeamId): void { $this->ciscoTeamId = $ciscoTeamId; }

    public function assignMember(int $employeeId): void
    {
        if (! in_array($employeeId, $this->memberIds, true)) {
            $this->memberIds[] = $employeeId;
        }
    }

    public function removeMember(int $employeeId): void
    {
        $this->memberIds = array_values(array_filter(
            $this->memberIds,
            fn ($id) => $id !== $employeeId,
        ));
    }

    public function hasMember(int $employeeId): bool
    {
        return in_array($employeeId, $this->memberIds, true);
    }
}
