<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Aggregates;

use App\Modules\CommunicationsModule\Domain\Enums\ContentStatus;
use App\Modules\CommunicationsModule\Domain\Events\CommentAdded;
use App\Modules\CommunicationsModule\Domain\Events\ContentModerated;
use App\Modules\CommunicationsModule\Domain\Events\NewsCreated;
use App\Modules\CommunicationsModule\Domain\Events\NewsPublished;
use App\Modules\CommunicationsModule\Domain\ValueObjects\DateRange;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ModerationDecision;
use App\Modules\CommunicationsModule\Domain\ValueObjects\NewsContent;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;
use DateTimeImmutable;

final class News
{
    private ?int $id = null;

    private array $events = [];

    private ContentStatus $status;

    private array $categoryIds = [];

    private array $tagIds = [];

    /** @var Comment[] */
    private array $comments = [];

    private function __construct(
        private NewsContent $content,
        private PersonId $authorId,
        private DateRange $dateRange,
        private bool $isActive,
        ContentStatus $status,
        private ?ModerationDecision $moderation = null,
        private ?DateTimeImmutable $publishedAt = null,
        private ?DateTimeImmutable $createdAt = null,
    ) {
        $this->status = $status;
        $this->createdAt ??= new DateTimeImmutable();
    }

    public static function draft(
        NewsContent $content,
        PersonId $authorId,
        DateRange $dateRange,
        bool $isActive,
    ): self {
        $news = new self(
            content: $content,
            authorId: $authorId,
            dateRange: $dateRange,
            isActive: $isActive,
            status: ContentStatus::Draft,
        );

        $news->events[] = new NewsCreated(
            newsId: 'pending',
            authorId: $authorId->value(),
            title: $content->title(),
            status: ContentStatus::Draft->value,
        );

        return $news;
    }

    public static function submitForReview(
        NewsContent $content,
        PersonId $authorId,
        DateRange $dateRange,
        bool $isActive,
    ): self {
        $news = new self(
            content: $content,
            authorId: $authorId,
            dateRange: $dateRange,
            isActive: $isActive,
            status: ContentStatus::PendingReview,
        );

        $news->events[] = new NewsCreated(
            newsId: 'pending',
            authorId: $authorId->value(),
            title: $content->title(),
            status: ContentStatus::PendingReview->value,
        );

        return $news;
    }

    public function applyModeration(ModerationDecision $decision): void
    {
        if (! $this->status->canTransitionTo(
            $decision->isApproval() ? ContentStatus::Published : ContentStatus::Draft
        )) {
            throw new \DomainException("Cannot moderate content in status: {$this->status->value}");
        }

        if ($decision->isApproval()) {
            $this->status = ContentStatus::Published;
            $this->publishedAt = new DateTimeImmutable();
            $this->isActive = true;
            $this->events[] = new NewsPublished($this->id ?? 'pending', $this->content->title());
        } elseif ($decision->isRejection()) {
            $this->status = ContentStatus::Draft;
        }

        $this->moderation = $decision;

        $this->events[] = new ContentModerated(
            contentType: 'news',
            contentId: $this->id ?? 'pending',
            action: $decision->action(),
            moderatorId: $decision->moderatorId()->value(),
            notes: $decision->notes(),
        );
    }

    public function archive(): void
    {
        $this->status = ContentStatus::Archived;
        $this->isActive = false;
    }

    public function publish(): void
    {
        $this->status = ContentStatus::Published;
        $this->publishedAt = new DateTimeImmutable();
        $this->isActive = true;

        $this->events[] = new NewsPublished($this->id ?? 'pending', $this->content->title());
    }

    public function addComment(Comment $comment): void
    {
        $this->comments[] = $comment;

        $this->events[] = new CommentAdded(
            commentId: 'pending',
            newsId: $this->id ?? 'pending',
            authorId: $comment->authorId()->value(),
            content: $comment->content(),
            parentId: $comment->parentId(),
        );
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function content(): NewsContent
    {
        return $this->content;
    }

    public function authorId(): PersonId
    {
        return $this->authorId;
    }

    public function status(): ContentStatus
    {
        return $this->status;
    }

    public function dateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setCategoryIds(array $ids): void
    {
        $this->categoryIds = $ids;
    }

    public function setTagIds(array $ids): void
    {
        $this->tagIds = $ids;
    }

    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }
}
