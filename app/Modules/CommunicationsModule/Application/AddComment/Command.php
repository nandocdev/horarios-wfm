<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\AddComment;

final readonly class Command
{
    public function __construct(
        public int $newsId,
        public int $userId,
        public string $content,
        public ?int $parentId = null,
    ) {}
}
