<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\ModerateContent;

final readonly class Command
{
    public function __construct(
        public string $action,
        public string $contentType,
        public int $contentId,
        public int $moderatorId,
        public ?string $notes = null,
    ) {}
}
