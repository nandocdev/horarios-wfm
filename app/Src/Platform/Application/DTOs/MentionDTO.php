<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\DTOs;

final readonly class MentionDTO
{
    public function __construct(
        public int $mentionedUserId,
        public string $context,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            mentionedUserId: (int) ($data['mentioned_user_id'] ?? $data['mentionedUserId'] ?? 0),
            context: $data['context'] ?? '',
        );
    }
}
