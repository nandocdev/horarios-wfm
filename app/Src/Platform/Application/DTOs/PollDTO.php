<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\DTOs;

final readonly class PollDTO
{
    public function __construct(
        public string $question,
        public array $options,
        public bool $isActive,
        public ?string $expiresAt = null,
        public ?string $scheduledAt = null,
        public ?string $archiveAt = null,
        public array $categoryIds = [],
        public array $tagIds = [],
        public string $workflowAction = 'save_draft',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            question: $data['question'],
            options: $data['options'],
            isActive: $data['is_active'] ?? true,
            expiresAt: $data['expires_at'] ?? null,
            scheduledAt: $data['scheduled_at'] ?? null,
            archiveAt: $data['archive_at'] ?? null,
            categoryIds: array_map('intval', $data['category_ids'] ?? []),
            tagIds: array_map('intval', $data['tag_ids'] ?? []),
            workflowAction: $data['workflow_action'] ?? 'save_draft',
        );
    }
}
