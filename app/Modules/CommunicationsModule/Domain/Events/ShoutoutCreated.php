<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Events;

final readonly class ShoutoutCreated
{
    public function __construct(
        public string $shoutoutId,
        public int $authorId,
    ) {}
}
