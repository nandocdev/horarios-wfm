<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Domain\Aggregates;

use App\Modules\AuditModule\Domain\Events\AuditEntryCreated;
use App\Modules\AuditModule\Domain\ValueObjects\AuditAction;
use App\Modules\AuditModule\Domain\ValueObjects\EntityId;
use App\Modules\AuditModule\Domain\ValueObjects\EntityType;
use App\Modules\AuditModule\Domain\ValueObjects\IpAddress;
use App\Modules\AuditModule\Domain\ValueObjects\SnapshotData;
use App\Modules\AuditModule\Domain\ValueObjects\UserId;
use DateTimeImmutable;

final class AuditLogEntry
{
    private ?int $id = null;

    private array $events = [];

    public function __construct(
        private EntityType $entityType,
        private EntityId $entityId,
        private AuditAction $action,
        private ?SnapshotData $before = null,
        private ?SnapshotData $after = null,
        private ?UserId $userId = null,
        private ?IpAddress $ipAddress = null,
        private ?DateTimeImmutable $createdAt = null,
    ) {
        $this->createdAt ??= new DateTimeImmutable();
    }

    public static function record(
        string $entityType,
        string|int $entityId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?int $userId = null,
        ?string $ipAddress = null,
    ): self {
        $entry = new self(
            entityType: new EntityType($entityType),
            entityId: new EntityId($entityId),
            action: AuditAction::fromString($action),
            before: new SnapshotData($before),
            after: new SnapshotData($after),
            userId: new UserId($userId),
            ipAddress: $ipAddress !== null ? new IpAddress($ipAddress) : null,
        );

        $entry->events[] = new AuditEntryCreated($entry);

        return $entry;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function entityType(): EntityType
    {
        return $this->entityType;
    }

    public function entityId(): EntityId
    {
        return $this->entityId;
    }

    public function action(): AuditAction
    {
        return $this->action;
    }

    public function before(): ?SnapshotData
    {
        return $this->before;
    }

    public function after(): ?SnapshotData
    {
        return $this->after;
    }

    public function userId(): ?UserId
    {
        return $this->userId;
    }

    public function ipAddress(): ?IpAddress
    {
        return $this->ipAddress;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }
}
