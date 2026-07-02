<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\UpdateNews;

final readonly class Command
{
    public function __construct(
        public int $newsId,
        public string $title,
        public string $slug,
        public string $content,
        public ?string $excerpt = null,
        public ?string $publishedAt = null,
        public ?string $scheduledAt = null,
        public ?string $archiveAt = null,
        public bool $isActive = true,
        public array $categoryIds = [],
        public array $tagIds = [],
        public mixed $featuredImage = null,
        public array $attachments = [],
    ) {}
}
