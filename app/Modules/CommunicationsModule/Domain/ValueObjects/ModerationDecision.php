<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\ValueObjects;

final readonly class ModerationDecision
{
    public function __construct(
        private string $action,
        private PersonId $moderatorId,
        private ?string $notes = null,
    ) {
        if (! in_array($this->action, ['approve', 'reject', 'archive', 'submit_for_review'], true)) {
            throw new \InvalidArgumentException("Invalid moderation action: {$this->action}");
        }
    }

    public function action(): string
    {
        return $this->action;
    }

    public function moderatorId(): PersonId
    {
        return $this->moderatorId;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function isApproval(): bool
    {
        return $this->action === 'approve';
    }

    public function isRejection(): bool
    {
        return $this->action === 'reject';
    }
}
