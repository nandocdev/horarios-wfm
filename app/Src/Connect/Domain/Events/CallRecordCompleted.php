<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Events;

use App\Src\Connect\Domain\Entities\CallRecord;
use App\Src\Shared\Domain\Events\DomainEvent;

final class CallRecordCompleted extends DomainEvent
{
    public function __construct(
        public readonly CallRecord $record,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'connect.call.record_completed';
    }
}
