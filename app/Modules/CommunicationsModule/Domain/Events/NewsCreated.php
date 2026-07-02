<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Events;

final readonly class NewsCreated
{
    public function __construct(
        public string $newsId,
        public int $authorId,
        public string $title,
        public string $status,
    ) {}
}
