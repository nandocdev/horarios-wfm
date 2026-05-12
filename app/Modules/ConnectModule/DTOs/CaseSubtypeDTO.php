<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\DTOs;

readonly class CaseSubtypeDTO
{
    public function __construct(
        public int $queueId,
        public string $code,
        public string $name,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}

    public static function fromForm(array $data): self
    {
        return new self(
            queueId: (int) ($data['queue_id'] ?? 0),
            code: trim($data['code']),
            name: trim($data['name']),
            description: $data['description'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }
}
