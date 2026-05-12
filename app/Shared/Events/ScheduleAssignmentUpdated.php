<?php

declare(strict_types=1);

namespace App\Shared\Events;

use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScheduleAssignmentUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WeeklyScheduleAssignment $assignment,
        public int|string $updatedByUserId
    ) {}
}
