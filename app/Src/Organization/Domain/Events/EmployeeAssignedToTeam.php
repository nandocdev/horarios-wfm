<?php

declare(strict_types=1);

namespace App\Src\Organization\Domain\Events;

use App\Src\Organization\Domain\Entities\TeamMember;
use App\Src\Shared\Domain\Events\DomainEvent;

final class EmployeeAssignedToTeam extends DomainEvent
{
    public function __construct(
        public readonly TeamMember $teamMember,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'organization.team.employee_assigned';
    }
}
