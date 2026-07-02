<?php

declare(strict_types=1);

namespace App\Src\Workflows\Application\DTOs;

final readonly class ProcessApprovalDTO
{
    public function __construct(
        public int $approvalRequestId,
        public int $approverId,
        public string $action,
        public ?string $comment = null,
    ) {}
}
