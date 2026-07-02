<?php

declare(strict_types=1);

namespace App\Src\Organization\Domain\Events;

use App\Src\Organization\Domain\Entities\Team;
use App\Src\Shared\Domain\Events\DomainEvent;

final class TeamCreated extends DomainEvent
{
    public function __construct(
        public readonly Team $team,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'organization.team.created';
    }
}
