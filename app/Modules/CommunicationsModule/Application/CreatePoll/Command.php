<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\CreatePoll;

final readonly class Command
{
    /** @param array<int, array{label: string, value: string, color?: string}> $options */
    public function __construct(
        public string $question,
        public array $options,
        public ?string $expiresAt = null,
        public ?string $scheduledAt = null,
        public ?string $archiveAt = null,
        public bool $isActive = true,
        public string $workflowAction = 'draft',
    ) {}
}
