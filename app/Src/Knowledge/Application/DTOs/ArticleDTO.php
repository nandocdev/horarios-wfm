<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Application\DTOs;

use DateTimeImmutable;

final readonly class ArticleDTO
{
    public function __construct(
        public string $title,
        public string $content,
        public ?string $summary = null,
        public ?int $categoryId = null,
        public string $status = 'draft',
        public ?DateTimeImmutable $publishedAt = null,
        public ?DateTimeImmutable $expiresAt = null,
        public array $tagNames = [],
        public array $queueIds = [],
    ) {}
}
