<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Services;

use App\Modules\CommunicationsModule\Domain\Enums\ContentStatus;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ModerationDecision;

final class ModerationService
{
    public function canModerate(object $content, ModerationDecision $decision): bool
    {
        if (! method_exists($content, 'status')) {
            return false;
        }

        $status = $content->status();

        if (! $status instanceof ContentStatus) {
            return false;
        }

        $target = match (true) {
            $decision->isApproval() => ContentStatus::Published,
            $decision->isRejection() => ContentStatus::Draft,
            default => null,
        };

        return $target !== null && $status->canTransitionTo($target);
    }

    public function needsModeration(object $content): bool
    {
        return method_exists($content, 'status')
            && $content->status() === ContentStatus::PendingReview;
    }
}
