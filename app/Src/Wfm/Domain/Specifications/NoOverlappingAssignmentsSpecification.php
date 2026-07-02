<?php

declare(strict_types=1);

namespace App\Src\Wfm\Domain\Specifications;

use App\Src\Wfm\Domain\Entities\ScheduleAssignment;

final class NoOverlappingAssignmentsSpecification
{
    public function isSatisfiedBy(ScheduleAssignment $new, array $existing): bool
    {
        foreach ($existing as $existingAssignment) {
            if (! $existingAssignment instanceof ScheduleAssignment) {
                continue;
            }

            if ($existingAssignment->employeeId() !== $new->employeeId()) {
                continue;
            }

            if ($existingAssignment->dayOfWeek() !== $new->dayOfWeek()) {
                continue;
            }

            if ($this->timesOverlap($new, $existingAssignment)) {
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return 'El empleado ya tiene un turno asignado que se traslapa con el nuevo horario.';
    }

    private function timesOverlap(ScheduleAssignment $a, ScheduleAssignment $b): bool
    {
        if ($a->startTime() === null || $a->endTime() === null ||
            $b->startTime() === null || $b->endTime() === null) {
            return false;
        }

        return $a->startTime() < $b->endTime() && $a->endTime() > $b->startTime();
    }
}
