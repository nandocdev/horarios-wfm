<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Events;

use App\Src\Connect\Domain\Entities\CallEvent;
use App\Src\Shared\Domain\Events\DomainEvent;

final class CallEventReceived extends DomainEvent
{
    public function __construct(
        public readonly CallEvent $event,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'connect.call.event_received';
    }
}
