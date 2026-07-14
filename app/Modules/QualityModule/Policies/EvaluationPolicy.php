<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\QualityModule\Models\Evaluation;

class EvaluationPolicy
{
    public function view(User $user, Evaluation $evaluation): bool
    {
        return $user->hasPermissionTo('quality.evaluations.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('quality.evaluations.create');
    }

    public function delete(User $user, Evaluation $evaluation): bool
    {
        if ($evaluation->feedbacks()->exists() || $evaluation->calibrations()->exists()) {
            return false;
        }

        return $user->hasPermissionTo('quality.evaluations.delete');
    }

    public function calibrate(User $user, Evaluation $evaluation): bool
    {
        return $user->hasPermissionTo('quality.calibrations.create')
            && $evaluation->status === 'activa';
    }
}
