<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\AbsenceReasonCode;
use Illuminate\Support\Facades\DB;

class SaveAbsenceReasonAction
{
    public function execute(array $data, ?AbsenceReasonCode $model = null): AbsenceReasonCode
    {
        return DB::transaction(function () use ($data, $model) {
            $model = $model ?? new AbsenceReasonCode;
            $model->fill($data);
            $model->save();

            return $model;
        });
    }
}
