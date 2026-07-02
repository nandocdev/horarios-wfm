<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Events;

use App\Src\Connect\Domain\Entities\AgentStateTransition;
use App\Src\Shared\Domain\Events\DomainEvent;

final class AgentStateTransitioned extends DomainEvent
{
    public function __construct(
        public readonly AgentStateTransition $transition,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'connect.agent.state_transitioned';
    }
}
