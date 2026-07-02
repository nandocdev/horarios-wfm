<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\CreateShoutout;

final readonly class Command
{
    public function __construct(
        public int $employeeId,
        public string $message,
        public int $authorId,
        public bool $isActive = true,
        public ?string $scheduledAt = null,
        public ?string $archiveAt = null,
        public string $workflowAction = 'draft',
        public array $categoryIds = [],
        public array $tagIds = [],
        public mixed $image = null,
    ) {}
}
