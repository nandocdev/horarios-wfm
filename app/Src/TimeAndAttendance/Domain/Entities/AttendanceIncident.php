<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Domain\Entities;

use DateTimeImmutable;

final class AttendanceIncident
{
    public const STATUS_OPEN = 'open';
    public const STATUS_JUSTIFIED = 'justified';
    public const STATUS_UNJUSTIFIED = 'unjustified';
    public const STATUS_RESOLVED = 'resolved';

    public function __construct(
        private ?int $id,
        private readonly int $employeeId,
        private readonly string $incidentTypeCode,
        private readonly DateTimeImmutable $incidentDate,
        private readonly ?DateTimeImmutable $startTime = null,
        private readonly ?DateTimeImmutable $endTime = null,
        private ?string $status = self::STATUS_OPEN,
        private ?string $userComment = null,
        private ?string $adminComment = null,
        private ?int $resolvedByUserId = null,
        private ?DateTimeImmutable $resolvedAt = null,
    ) {}

    public static function create(
        int $employeeId,
        string $incidentTypeCode,
        DateTimeImmutable $incidentDate,
        ?DateTimeImmutable $startTime = null,
        ?DateTimeImmutable $endTime = null,
    ): self {
        return new self(null, $employeeId, $incidentTypeCode, $incidentDate, $startTime, $endTime);
    }

    public function id(): ?int { return $this->id; }
    public function employeeId(): int { return $this->employeeId; }
    public function incidentTypeCode(): string { return $this->incidentTypeCode; }
    public function incidentDate(): DateTimeImmutable { return $this->incidentDate; }
    public function startTime(): ?DateTimeImmutable { return $this->startTime; }
    public function endTime(): ?DateTimeImmutable { return $this->endTime; }
    public function status(): ?string { return $this->status; }
    public function userComment(): ?string { return $this->userComment; }
    public function adminComment(): ?string { return $this->adminComment; }
    public function resolvedByUserId(): ?int { return $this->resolvedByUserId; }
    public function resolvedAt(): ?DateTimeImmutable { return $this->resolvedAt; }

    public function isOpen(): bool { return $this->status === self::STATUS_OPEN; }

    public function justify(string $comment, ?string $adminComment = null): void
    {
        $this->status = self::STATUS_JUSTIFIED;
        $this->userComment = $comment;
        if ($adminComment) $this->adminComment = $adminComment;
    }

    public function resolve(int $userId, string $comment): void
    {
        $this->status = self::STATUS_RESOLVED;
        $this->resolvedByUserId = $userId;
        $this->adminComment = $comment;
        $this->resolvedAt = new DateTimeImmutable();
    }
}
