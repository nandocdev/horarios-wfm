<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Aggregates;

use App\Modules\CommunicationsModule\Domain\Enums\ContentStatus;
use App\Modules\CommunicationsModule\Domain\Enums\ReactionType;
use App\Modules\CommunicationsModule\Domain\Events\ContentModerated;
use App\Modules\CommunicationsModule\Domain\Events\ReactionAdded;
use App\Modules\CommunicationsModule\Domain\Events\ReactionRemoved;
use App\Modules\CommunicationsModule\Domain\Events\ShoutoutCreated;
use App\Modules\CommunicationsModule\Domain\ValueObjects\DateRange;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ModerationDecision;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ShoutoutMessage;
use DateTimeImmutable;

final class Shoutout
{
    private ?int $id = null;

    private array $events = [];

    private ContentStatus $status;

    /** @var Reaction[] */
    private array $reactions = [];

    private array $categoryIds = [];

    private array $tagIds = [];

    private function __construct(
        private ShoutoutMessage $message,
        private PersonId $authorId,
        private DateRange $dateRange,
        private bool $isActive,
        ContentStatus $status,
        private ?ModerationDecision $moderation = null,
        private ?DateTimeImmutable $createdAt = null,
    ) {
        $this->status = $status;
        $this->createdAt ??= new DateTimeImmutable();
    }

    public static function draft(
        ShoutoutMessage $message,
        PersonId $authorId,
        DateRange $dateRange,
        bool $isActive,
    ): self {
        $shoutout = new self(
            message: $message,
            authorId: $authorId,
            dateRange: $dateRange,
            isActive: $isActive,
            status: ContentStatus::Draft,
        );

        $shoutout->events[] = new ShoutoutCreated($shoutout->id ?? 'pending', $authorId->value());

        return $shoutout;
    }

    public static function submitForReview(
        ShoutoutMessage $message,
        PersonId $authorId,
        DateRange $dateRange,
        bool $isActive,
    ): self {
        $shoutout = new self(
            message: $message,
            authorId: $authorId,
            dateRange: $dateRange,
            isActive: $isActive,
            status: ContentStatus::PendingReview,
        );

        $shoutout->events[] = new ShoutoutCreated($shoutout->id ?? 'pending', $authorId->value());

        return $shoutout;
    }

    public function toggleReaction(PersonId $userId, ReactionType $type): void
    {
        $existingKey = $this->findReactionKey($userId, $type);

        if ($existingKey !== null) {
            $removed = $this->reactions[$existingKey];
            unset($this->reactions[$existingKey]);
            $this->reactions = array_values($this->reactions);

            $this->events[] = new ReactionRemoved(
                reactionId: 'pending',
                shoutoutId: $this->id ?? 'pending',
                userId: $userId->value(),
                type: $type->value,
            );
        } else {
            $reaction = new Reaction(
                shoutoutId: $this->id,
                userId: $userId,
                type: $type,
            );
            $this->reactions[] = $reaction;

            $this->events[] = new ReactionAdded(
                reactionId: 'pending',
                shoutoutId: $this->id ?? 'pending',
                userId: $userId->value(),
                type: $type->value,
            );
        }
    }

    public function applyModeration(ModerationDecision $decision): void
    {
        if ($decision->isApproval()) {
            $this->status = ContentStatus::Published;
        } elseif ($decision->isRejection()) {
            $this->status = ContentStatus::Draft;
        }

        $this->moderation = $decision;

        $this->events[] = new ContentModerated(
            contentType: 'shoutout',
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

    public function setId(int $id): void
    {
        $this->id = $id;

        foreach ($this->reactions as $reaction) {
            $reaction->setShoutoutId($id);
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    private function findReactionKey(PersonId $userId, ReactionType $type): ?int
    {
        foreach ($this->reactions as $key => $reaction) {
            if ($reaction->userId()->equals($userId) && $reaction->type() === $type) {
                return $key;
            }
        }

        return null;
    }

    public function reactions(): array
    {
        return $this->reactions;
    }

    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }
}
