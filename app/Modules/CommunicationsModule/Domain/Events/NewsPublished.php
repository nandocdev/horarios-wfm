<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Events;

final readonly class NewsPublished
{
    public function __construct(
        public string $newsId,
        public string $title,
    ) {}
}
