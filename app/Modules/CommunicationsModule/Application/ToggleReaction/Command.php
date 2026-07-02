<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\ToggleReaction;

final readonly class Command
{
    public function __construct(
        public int $shoutoutId,
        public int $userId,
        public string $type,
    ) {}
}
