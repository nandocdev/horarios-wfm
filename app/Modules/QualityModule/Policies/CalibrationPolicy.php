<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\QualityModule\Enums\EvaluationStatus;
use App\Modules\QualityModule\Models\Evaluation;

class CalibrationPolicy
{
    public function create(User $user, Evaluation $evaluation): bool
    {
        return $user->hasPermissionTo('quality.calibrations.create')
            && in_array($evaluation->status, [
                EvaluationStatus::PendienteCalibracion->value,
                EvaluationStatus::Activa->value,
            ]);
    }
}
