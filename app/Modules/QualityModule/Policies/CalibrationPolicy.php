<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Policies;

use App\Modules\CoreModule\Models\User;

class CalibrationPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('quality.calibrations.create');
    }
}
