<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Entities;

use App\Modules\CommunicationsModule\Domain\ValueObjects\ContentBody;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;
use DateTimeImmutable;

final class Comment
{
    private ?int $id = null;

    public function __construct(
        private ContentBody $content,
        private PersonId $authorId,
        private ?int $parentId = null,
        private bool $isActive = true,
        private ?DateTimeImmutable $publishedAt = null,
        private ?int $newsId = null,
    ) {
        $this->publishedAt ??= new DateTimeImmutable();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function content(): string
    {
        return $this->content->value();
    }

    public function contentBody(): ContentBody
    {
        return $this->content;
    }

    public function authorId(): PersonId
    {
        return $this->authorId;
    }

    public function parentId(): ?int
    {
        return $this->parentId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function newsId(): ?int
    {
        return $this->newsId;
    }

    public function setNewsId(int $newsId): void
    {
        $this->newsId = $newsId;
    }
}
