<?php

declare(strict_types=1);

namespace App\Src\Workflows\Domain\Events;

use App\Src\Shared\Domain\Events\DomainEvent;
use App\Src\Workflows\Domain\Entities\ApprovalRequest;

final class ApprovalRequestProcessed extends DomainEvent
{
    public function __construct(
        public readonly ApprovalRequest $request,
        public readonly string $action,
        public readonly int $approverId,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'workflows.request.processed';
    }
}
