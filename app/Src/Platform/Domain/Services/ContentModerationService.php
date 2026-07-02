<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Services;

use App\Src\Platform\Domain\ValueObjects\ContentStatus;
use App\Src\Shared\Domain\Exceptions\DomainException;

final class ContentModerationService {
    private const array TRANSITIONS = [
        ContentStatus::Draft->value => [ContentStatus::PendingReview, ContentStatus::Archived],
        ContentStatus::PendingReview->value => [ContentStatus::Published, ContentStatus::Draft, ContentStatus::Archived],
        ContentStatus::Published->value => [ContentStatus::Archived, ContentStatus::Draft],
        ContentStatus::Archived->value => [ContentStatus::Draft],
    ];

    public function approve(ContentStatus $current): ContentStatus
    {
        if ($current !== ContentStatus::PendingReview) {
            throw new DomainException(
                sprintf('Cannot approve content with status "%s". Only pending review content can be approved.', $current->label())
            );
        }

        return ContentStatus::Published;
    }

    public function reject(ContentStatus $current): ContentStatus
    {
        if ($current !== ContentStatus::PendingReview) {
            throw new DomainException(
                sprintf('Cannot reject content with status "%s". Only pending review content can be rejected.', $current->label())
            );
        }

        return ContentStatus::Draft;
    }

    public function submitForReview(ContentStatus $current): ContentStatus
    {
        if ($current !== ContentStatus::Draft) {
            throw new DomainException(
                sprintf('Cannot submit content with status "%s" for review. Only draft content can be submitted.', $current->label())
            );
        }

        return ContentStatus::PendingReview;
    }

    public function archive(ContentStatus $current): ContentStatus
    {
        if (!in_array($current, [ContentStatus::Draft, ContentStatus::PendingReview, ContentStatus::Published], true)) {
            throw new DomainException(
                sprintf('Cannot archive content with status "%s".', $current->label())
            );
        }

        return ContentStatus::Archived;
    }

    public function isValidTransition(ContentStatus $current, ContentStatus $target): bool
    {
        $allowed = self::TRANSITIONS[$current->value] ?? [];

        return in_array($target, $allowed, true);
    }
}
