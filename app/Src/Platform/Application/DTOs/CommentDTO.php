<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\DTOs;

final readonly class CommentDTO
{
    public function __construct(
        public string $content,
        public ?int $parentId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            content: $data['content'],
            parentId: $data['parent_id'] ?? null,
        );
    }
}
