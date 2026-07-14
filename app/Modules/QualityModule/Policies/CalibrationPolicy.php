<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Policies;

use App\Modules\CoreModule\Models\User;

class CalibrationPolicy
{
    public function create(User $user, \App\Modules\QualityModule\Models\Evaluation $evaluation): bool
    {
        return $user->hasPermissionTo('quality.calibrations.create')
            && in_array($evaluation->status, [
                \App\Modules\QualityModule\Enums\EvaluationStatus::PendienteCalibracion->value,
                \App\Modules\QualityModule\Enums\EvaluationStatus::Activa->value,
            ]);
    }
}
