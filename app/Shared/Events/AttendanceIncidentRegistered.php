<?php

declare(strict_types=1);

namespace App\Shared\Events;

use App\Modules\OperationsModule\Models\AttendanceIncident;
use Illuminate\Foundation\Events\Dispatchable;

class AttendanceIncidentRegistered
{
    use Dispatchable;

    public function __construct(
        public AttendanceIncident $incident,
        public string $typeCode,
    ) {}
}
