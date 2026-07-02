<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Events;

final readonly class ContentModerated
{
    public function __construct(
        public string $contentType,
        public string $contentId,
        public string $action,
        public int $moderatorId,
        public ?string $notes,
    ) {}
}
