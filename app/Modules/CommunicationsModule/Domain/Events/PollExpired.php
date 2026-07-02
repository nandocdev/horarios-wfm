<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Events;

final readonly class PollExpired
{
    public function __construct(
        public string $pollId,
        public string $question,
    ) {}
}
