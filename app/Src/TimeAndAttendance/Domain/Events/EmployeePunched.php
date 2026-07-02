<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Domain\Events;

use App\Src\Shared\Domain\Events\DomainEvent;
use App\Src\TimeAndAttendance\Domain\Entities\AttendancePunch;

final class EmployeePunched extends DomainEvent
{
    public function __construct(
        public readonly AttendancePunch $punch,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'time_and_attendance.employee_punched';
    }
}
