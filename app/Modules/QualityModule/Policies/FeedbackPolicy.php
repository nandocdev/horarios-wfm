<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\QualityModule\Enums\EvaluationStatus;
use App\Modules\QualityModule\Models\Evaluation;

class FeedbackPolicy
{
    public function create(User $user, Evaluation $evaluation): bool
    {
        return $user->hasPermissionTo('quality.feedback.create')
            && $evaluation->status === EvaluationStatus::Activa->value;
    }
}
