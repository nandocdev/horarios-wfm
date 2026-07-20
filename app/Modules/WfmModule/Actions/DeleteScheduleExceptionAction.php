<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\ScheduleException;

class DeleteScheduleExceptionAction
{
    public function execute(int $id): void
    {
        $exception = ScheduleException::findOrFail($id);
        $exception->delete();
    }
}
