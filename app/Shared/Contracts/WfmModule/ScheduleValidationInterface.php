<?php

declare(strict_types=1);

namespace App\Shared\Contracts\WfmModule;

interface ScheduleValidationInterface
{
    public function detectScheduleConflicts(string $date): array;
}
