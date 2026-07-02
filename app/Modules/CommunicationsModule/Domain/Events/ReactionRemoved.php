<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Events;

final readonly class ReactionRemoved
{
    public function __construct(
        public string $reactionId,
        public string $shoutoutId,
        public int $userId,
        public string $type,
    ) {}
}
