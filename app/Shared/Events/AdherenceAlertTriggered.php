<?php

declare(strict_types=1);

namespace App\Shared\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AdherenceAlertTriggered
{
    use Dispatchable;

    public function __construct(
        public mixed $employee,
        public string $alertType,
        public string $label,
        public int $durationSeconds,
    ) {}
}
