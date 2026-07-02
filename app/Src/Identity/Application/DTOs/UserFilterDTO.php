<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\DTOs;

final readonly class UserFilterDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $role = null,
        public ?bool $isActive = null,
        public ?int $teamId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            role: $data['role'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            teamId: isset($data['team_id']) ? (int) $data['team_id'] : null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'role' => $this->role,
            'is_active' => $this->isActive,
            'team_id' => $this->teamId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], fn ($v) => $v !== null);
    }
}
