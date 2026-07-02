<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Domain\Entities;

use DateTimeImmutable;

final class Article
{
    private ?int $id;
    private string $title;
    private string $slug;
    private ?string $summary;
    private string $content;
    private ?int $categoryId;
    private string $status;
    private int $version;
    private ?DateTimeImmutable $publishedAt;
    private ?DateTimeImmutable $expiresAt;
    private int $createdBy;
    private ?int $updatedBy;
    private array $tagNames;
    private array $queueIds;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEW = 'review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public function __construct(
        ?int $id,
        string $title,
        string $slug,
        ?string $summary,
        string $content,
        ?int $categoryId,
        string $status,
        int $version,
        ?DateTimeImmutable $publishedAt,
        ?DateTimeImmutable $expiresAt,
        int $createdBy,
        ?int $updatedBy,
        array $tagNames = [],
        array $queueIds = [],
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->slug = $slug;
        $this->summary = $summary;
        $this->content = $content;
        $this->categoryId = $categoryId;
        $this->status = $status;
        $this->version = $version;
        $this->publishedAt = $publishedAt;
        $this->expiresAt = $expiresAt;
        $this->createdBy = $createdBy;
        $this->updatedBy = $updatedBy;
        $this->tagNames = $tagNames;
        $this->queueIds = $queueIds;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public static function create(
        string $title,
        string $content,
        int $createdBy,
        ?string $summary = null,
        ?int $categoryId = null,
        string $status = self::STATUS_DRAFT,
        ?DateTimeImmutable $publishedAt = null,
        ?DateTimeImmutable $expiresAt = null,
        array $tagNames = [],
        array $queueIds = [],
    ): self {
        return new self(null, $title, '', $summary, $content, $categoryId, $status, 1, $publishedAt, $expiresAt, $createdBy, null, $tagNames, $queueIds);
    }

    public function id(): ?int { return $this->id; }
    public function title(): string { return $this->title; }
    public function slug(): string { return $this->slug; }
    public function summary(): ?string { return $this->summary; }
    public function content(): string { return $this->content; }
    public function categoryId(): ?int { return $this->categoryId; }
    public function status(): string { return $this->status; }
    public function version(): int { return $this->version; }
    public function publishedAt(): ?DateTimeImmutable { return $this->publishedAt; }
    public function expiresAt(): ?DateTimeImmutable { return $this->expiresAt; }
    public function createdBy(): int { return $this->createdBy; }
    public function updatedBy(): ?int { return $this->updatedBy; }
    public function tagNames(): array { return $this->tagNames; }
    public function queueIds(): array { return $this->queueIds; }

    public function publish(): void
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->publishedAt ??= new DateTimeImmutable();
    }
}
