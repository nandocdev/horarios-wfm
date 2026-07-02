<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\DTOs;

readonly class ModerationDTO
{
    public function __construct(
        public string $status,
        public ?int $approvedBy = null,
        public ?string $moderationNotes = null,
    ) {}

    public static function approve(?string $notes = null): self
    {
        return new self(
            status: 'published',
            approvedBy: auth()->id(),
            moderationNotes: $notes,
        );
    }

    public static function reject(string $notes): self
    {
        return new self(
            status: 'draft',
            approvedBy: auth()->id(),
            moderationNotes: $notes,
        );
    }

    public static function submitForReview(): self
    {
        return new self(status: 'pending_review');
    }

    public static function archive(): self
    {
        return new self(status: 'archived');
    }
}
