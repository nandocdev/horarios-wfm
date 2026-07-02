<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Archived = 'archived';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::PendingReview, self::Archived], true),
            self::PendingReview => in_array($target, [self::Published, self::Draft, self::Archived], true),
            self::Published => $target === self::Archived,
            self::Archived => false,
        };
    }
}
