<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\ScheduledActivityDefinition;
use Illuminate\Support\Facades\DB;

class SaveScheduledActivityAction
{
    public function execute(array $data, ?ScheduledActivityDefinition $model = null): ScheduledActivityDefinition
    {
        return DB::transaction(function () use ($data, $model) {
            $model = $model ?? new ScheduledActivityDefinition;
            $model->fill($data);
            $model->save();

            return $model;
        });
    }
}
