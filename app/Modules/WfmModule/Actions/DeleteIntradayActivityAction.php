<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\IntradayActivity;
use Illuminate\Support\Facades\DB;

final class DeleteIntradayActivityAction
{
    /**
     * Elimina una actividad intradía de forma transaccional.
     */
    public function execute(int $activityId): void
    {
        DB::transaction(function () use ($activityId) {
            $activity = IntradayActivity::findOrFail($activityId);
            $activity->delete();
        });
    }
}
