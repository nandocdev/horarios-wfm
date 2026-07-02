<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Events;

final readonly class MentionCreated
{
    public function __construct(
        public string $mentionId,
        public int $mentionedUserId,
        public int $mentionerUserId,
        public string $context,
    ) {}
}
