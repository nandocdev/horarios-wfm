<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\ActivityType;
use Illuminate\Support\Facades\DB;

class SaveActivityTypeAction
{
    public function execute(array $data, ?ActivityType $activityType = null): ActivityType
    {
        return DB::transaction(function () use ($data, $activityType) {
            $activityType = $activityType ?? new ActivityType;
            $activityType->fill($data);
            $activityType->save();

            return $activityType;
        });
    }
}
