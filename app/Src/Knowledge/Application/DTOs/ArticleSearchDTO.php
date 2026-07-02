<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Application\DTOs;

final readonly class ArticleSearchDTO
{
    public function __construct(
        public ?string $query = null,
        public ?int $categoryId = null,
        public ?string $tag = null,
        public ?string $status = 'published',
    ) {}
}
