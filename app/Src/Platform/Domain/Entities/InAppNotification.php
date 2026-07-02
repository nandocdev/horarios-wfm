<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Entities;

use DateTimeImmutable;

final class InAppNotification {
    private function __construct(
        private ?string $id,
        private int $userId,
        private string $type,
        private string $notifiableType,
        private int $notifiableId,
        private string $title,
        private ?string $message,
        private ?array $data,
        private bool $isRead,
        private ?DateTimeImmutable $readAt,
        private ?DateTimeImmutable $expiresAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        string $id,
        int $userId,
        string $type,
        string $notifiableType,
        int $notifiableId,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?DateTimeImmutable $expiresAt = null,
    ): self {
        $now = new DateTimeImmutable();
        return new self(
            id: $id,
            userId: $userId,
            type: $type,
            notifiableType: $notifiableType,
            notifiableId: $notifiableId,
            title: $title,
            message: $message,
            data: $data,
            isRead: false,
            readAt: null,
            expiresAt: $expiresAt,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromDatabase(
        string $id,
        int $userId,
        string $type,
        string $notifiableType,
        int $notifiableId,
        string $title,
        ?string $message,
        ?array $data,
        bool $isRead,
        ?DateTimeImmutable $readAt,
        ?DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            type: $type,
            notifiableType: $notifiableType,
            notifiableId: $notifiableId,
            title: $title,
            message: $message,
            data: $data,
            isRead: $isRead,
            readAt: $readAt,
            expiresAt: $expiresAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function id(): ?string { return $this->id; }
    public function userId(): int { return $this->userId; }
    public function type(): string { return $this->type; }
    public function notifiableType(): string { return $this->notifiableType; }
    public function notifiableId(): int { return $this->notifiableId; }
    public function title(): string { return $this->title; }
    public function message(): ?string { return $this->message; }
    public function data(): ?array { return $this->data; }
    public function isRead(): bool { return $this->isRead; }
    public function readAt(): ?DateTimeImmutable { return $this->readAt; }
    public function expiresAt(): ?DateTimeImmutable { return $this->expiresAt; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
}
