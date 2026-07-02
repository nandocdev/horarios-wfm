<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Domain\Events;

use App\Src\Shared\Domain\Events\DomainEvent;
use App\Src\TimeAndAttendance\Domain\Entities\AttendanceIncident;

final class AttendanceIncidentRecorded extends DomainEvent
{
    public function __construct(
        public readonly AttendanceIncident $incident,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'time_and_attendance.incident_recorded';
    }
}
