<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\DTOs;

final readonly class ShoutoutDTO
{
    public function __construct(
        public int $employeeId,
        public string $message,
        public bool $isActive,
        public ?string $scheduledAt = null,
        public ?string $archiveAt = null,
        public array $categoryIds = [],
        public array $tagIds = [],
        public string $workflowAction = 'save_draft',
        public mixed $image = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: (int) ($data['employee_id'] ?? $data['employeeId'] ?? 0),
            message: $data['message'],
            isActive: $data['is_active'] ?? true,
            scheduledAt: $data['scheduled_at'] ?? null,
            archiveAt: $data['archive_at'] ?? null,
            categoryIds: array_map('intval', $data['category_ids'] ?? $data['categoryIds'] ?? []),
            tagIds: array_map('intval', $data['tag_ids'] ?? $data['tagIds'] ?? []),
            workflowAction: $data['workflow_action'] ?? $data['workflowAction'] ?? 'save_draft',
            image: $data['image'] ?? null,
        );
    }
}
