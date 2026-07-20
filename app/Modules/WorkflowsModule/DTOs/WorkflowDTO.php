<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\DTOs;

readonly class WorkflowDTO
{
    public function __construct(
        public string $requestable_type,
        public int $requestable_id,
        public int $requester_id,
        public string $type,
        public ?string $reason = null,
        public ?array $data = null,
    ) {}
}
