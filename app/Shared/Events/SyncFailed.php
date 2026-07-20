<?php

declare(strict_types=1);

namespace App\Shared\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $source,
        public readonly string $message,
        public readonly int $consecutiveFailures,
    ) {}
}
