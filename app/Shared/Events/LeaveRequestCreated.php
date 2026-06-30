<?php

declare(strict_types=1);

namespace App\Shared\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveRequestCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public mixed $leaveRequest,
        public int|string $requestedByUserId,
    ) {}
}
