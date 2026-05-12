<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\AgentState;
use Illuminate\Support\Facades\DB;

class SaveAgentStateAction
{
    public function execute(array $data, ?AgentState $model = null): AgentState
    {
        return DB::transaction(function () use ($data, $model) {
            $model = $model ?? new AgentState;
            $model->fill($data);
            $model->save();

            return $model;
        });
    }
}
