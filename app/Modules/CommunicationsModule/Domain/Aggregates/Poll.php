<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Aggregates;

use App\Modules\CommunicationsModule\Domain\Enums\ContentStatus;
use App\Modules\CommunicationsModule\Domain\Events\ContentModerated;
use App\Modules\CommunicationsModule\Domain\Events\PollCreated;
use App\Modules\CommunicationsModule\Domain\Events\PollExpired;
use App\Modules\CommunicationsModule\Domain\ValueObjects\DateRange;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ModerationDecision;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PollOption;
use DateTimeImmutable;

final class Poll
{
    private ?int $id = null;

    private array $events = [];

    private ContentStatus $status;

    /** @var PollResponse[] */
    private array $responses = [];

    private array $categoryIds = [];

    private array $tagIds = [];

    public function __construct(
        private string $question,
        private array $options,
        private DateRange $dateRange,
        private bool $isActive,
        ContentStatus $status,
        private ?PersonId $authorId = null,
        private ?ModerationDecision $moderation = null,
        private ?DateTimeImmutable $reminderSentAt = null,
        private ?DateTimeImmutable $createdAt = null,
    ) {
        if (count($this->options) < 2 || count($this->options) > 10) {
            throw new \DomainException('Poll must have between 2 and 10 options');
        }
        $this->status = $status;
        $this->createdAt ??= new DateTimeImmutable();
    }

    public static function draft(
        string $question,
        array $options,
        DateRange $dateRange,
        bool $isActive,
        ?PersonId $authorId = null,
    ): self {
        $poll = new self(
            question: $question,
            options: $options,
            dateRange: $dateRange,
            isActive: $isActive,
            status: ContentStatus::Draft,
            authorId: $authorId,
        );

        $poll->events[] = new PollCreated('pending');

        return $poll;
    }

    public static function submitForReview(
        string $question,
        array $options,
        DateRange $dateRange,
        bool $isActive,
        ?PersonId $authorId = null,
    ): self {
        $poll = new self(
            question: $question,
            options: $options,
            dateRange: $dateRange,
            isActive: $isActive,
            status: ContentStatus::PendingReview,
            authorId: $authorId,
        );

        $poll->events[] = new PollCreated('pending');

        return $poll;
    }

    public function recordVote(PersonId $userId, string $answer): PollResponse
    {
        if ($this->status !== ContentStatus::Published) {
            throw new \DomainException('Cannot vote on a non-published poll');
        }

        $optionIndex = null;
        $foundOption = null;
        foreach ($this->options as $i => $option) {
            if ($option->value() === $answer) {
                $optionIndex = $i;
                $foundOption = $option;
                break;
            }
        }

        if ($foundOption === null) {
            throw new \DomainException("Invalid poll option: {$answer}");
        }

        $existingVote = $this->findVoteByUser($userId);
        if ($existingVote !== null) {
            throw new \DomainException('User has already voted on this poll');
        }

        $this->options[$optionIndex] = $foundOption->incrementVotes();

        $response = new PollResponse($this->id, $userId, $answer);
        $this->responses[] = $response;

        return $response;
    }

    public function hasVoted(PersonId $userId): bool
    {
        return $this->findVoteByUser($userId) !== null;
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
            contentType: 'poll',
            contentId: $this->id ?? 'pending',
            action: $decision->action(),
            moderatorId: $decision->moderatorId()->value(),
            notes: $decision->notes(),
        );
    }

    public function markExpired(): void
    {
        $this->status = ContentStatus::Archived;
        $this->isActive = false;

        $this->events[] = new PollExpired(
            pollId: $this->id ?? 'pending',
            question: $this->question,
        );
    }

    public function markReminderSent(): void
    {
        $this->reminderSentAt = new DateTimeImmutable();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    private function findVoteByUser(PersonId $userId): ?PollResponse
    {
        foreach ($this->responses as $response) {
            if ($response->userId()->equals($userId)) {
                return $response;
            }
        }

        return null;
    }

    public function options(): array
    {
        return $this->options;
    }

    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }
}
