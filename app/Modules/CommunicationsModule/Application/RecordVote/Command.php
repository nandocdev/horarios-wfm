<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\RecordVote;

final readonly class Command
{
    public function __construct(
        public int $pollId,
        public int $userId,
        public string $answer,
    ) {}
}
