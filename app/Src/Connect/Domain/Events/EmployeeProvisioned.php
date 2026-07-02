<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Events;

use App\Src\Connect\Application\DTOs\SyncEmployeeDTO;
use App\Src\Shared\Domain\Events\DomainEvent;

final class EmployeeProvisioned extends DomainEvent
{
    public function __construct(
        public readonly SyncEmployeeDTO $dto,
        public readonly array $ciscoResponse,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'connect.cisco.employee_provisioned';
    }
}
