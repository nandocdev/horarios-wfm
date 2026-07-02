<?php

declare(strict_types=1);

namespace App\Src\Wfm\Domain\Entities;

use DateTimeImmutable;

final class WeeklySchedule
{
    private ?int $id;
    private DateTimeImmutable $weekStartDate;
    private DateTimeImmutable $weekEndDate;
    private string $status;
    private ?DateTimeImmutable $publishedAt;
    private array $assignments;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public function __construct(
        ?int $id,
        DateTimeImmutable $weekStartDate,
        DateTimeImmutable $weekEndDate,
        string $status = self::STATUS_DRAFT,
        ?DateTimeImmutable $publishedAt = null,
        array $assignments = [],
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->weekStartDate = $weekStartDate;
        $this->weekEndDate = $weekEndDate;
        $this->status = $status;
        $this->publishedAt = $publishedAt;
        $this->assignments = $assignments;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public static function create(DateTimeImmutable $weekStartDate, DateTimeImmutable $weekEndDate): self
    {
        return new self(null, $weekStartDate, $weekEndDate, self::STATUS_DRAFT, null, []);
    }

    public function id(): ?int { return $this->id; }
    public function weekStartDate(): DateTimeImmutable { return $this->weekStartDate; }
    public function weekEndDate(): DateTimeImmutable { return $this->weekEndDate; }
    public function status(): string { return $this->status; }
    public function publishedAt(): ?DateTimeImmutable { return $this->publishedAt; }
    public function assignments(): array { return $this->assignments; }
    public function isDraft(): bool { return $this->status === self::STATUS_DRAFT; }
    public function isPublished(): bool { return $this->status === self::STATUS_PUBLISHED; }

    public function publish(DateTimeImmutable $now): void
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->publishedAt = $now;
    }

    public function addAssignment(ScheduleAssignment $assignment): void
    {
        $this->assignments[] = $assignment;
    }

    public function addAssignments(array $assignments): void
    {
        foreach ($assignments as $assignment) {
            $this->addAssignment($assignment);
        }
    }
}
