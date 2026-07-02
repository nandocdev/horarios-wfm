<?php

declare(strict_types=1);

namespace App\Src\Wfm\Domain\Events;

use App\Src\Shared\Domain\Events\DomainEvent;
use App\Src\Wfm\Domain\Entities\IntradayActivity;

final class IntradayActivityAssigned extends DomainEvent
{
    public function __construct(
        public readonly IntradayActivity $activity,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'wfm.intraday.activity_assigned';
    }
}
