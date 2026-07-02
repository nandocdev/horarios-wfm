<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Entities;

use App\Src\Shared\Domain\Exceptions\InvalidArgumentDomainException;
use DateTimeImmutable;

final class AuditLog {
    private function __construct(
        private ?int $id,
        private string $entityType,
        private int|string|null $entityId,
        private string $action,
        private ?array $before,
        private ?array $after,
        private ?string $ipAddress,
        private ?int $userId,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(
        string $entityType,
        int|string|null $entityId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?string $ipAddress = null,
        ?int $userId = null,
    ): self {
        return new self(
            id: null,
            entityType: $entityType,
            entityId: $entityId,
            action: $action,
            before: $before,
            after: $after,
            ipAddress: $ipAddress,
            userId: $userId,
            createdAt: new DateTimeImmutable(),
        );
    }

    public static function fromDatabase(
        int $id,
        string $entityType,
        int|string|null $entityId,
        string $action,
        ?array $before,
        ?array $after,
        ?string $ipAddress,
        ?int $userId,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            entityType: $entityType,
            entityId: $entityId,
            action: $action,
            before: $before,
            after: $after,
            ipAddress: $ipAddress,
            userId: $userId,
            createdAt: $createdAt,
        );
    }

    public function id(): ?int {
        return $this->id;
    }

    public function entityType(): string {
        return $this->entityType;
    }

    public function entityId(): int|string|null {
        return $this->entityId;
    }

    public function action(): string {
        return $this->action;
    }

    public function before(): ?array {
        return $this->before;
    }

    public function after(): ?array {
        return $this->after;
    }

    public function ipAddress(): ?string {
        return $this->ipAddress;
    }

    public function userId(): ?int {
        return $this->userId;
    }

    public function createdAt(): DateTimeImmutable {
        return $this->createdAt;
    }
}
