<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Events;

final readonly class CommentAdded
{
    public function __construct(
        public string $commentId,
        public string $newsId,
        public int $authorId,
        public string $content,
        public ?int $parentId,
    ) {}
}
