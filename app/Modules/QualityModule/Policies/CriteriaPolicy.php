<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\QualityModule\Models\Criteria;

class CriteriaPolicy
{
    public function view(User $user, Criteria $criteria): bool
    {
        return $user->hasPermissionTo('quality.criteria.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('quality.criteria.create');
    }

    public function update(User $user, Criteria $criteria): bool
    {
        return $user->hasPermissionTo('quality.criteria.update');
    }
}
