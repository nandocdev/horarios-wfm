<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\DTOs;

final readonly class InAppNotificationDTO
{
    public function __construct(
        public int $userId,
        public string $type,
        public string $title,
        public string $message,
        public array $data = [],
        public ?string $expiresAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) ($data['user_id'] ?? $data['userId'] ?? 0),
            type: $data['type'],
            title: $data['title'],
            message: $data['message'],
            data: $data['data'] ?? [],
            expiresAt: $data['expires_at'] ?? $data['expiresAt'] ?? null,
        );
    }
}
