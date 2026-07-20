<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Models\ApprovedIntradayPeriod;

class ApprovedIntradayPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('approved_intraday_periods.viewAny');
    }

    public function view(User $user, ApprovedIntradayPeriod $approvedIntradayPeriod): bool
    {
        return $user->hasPermissionTo('approved_intraday_periods.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('approved_intraday_periods.create');
    }

    public function update(User $user, ApprovedIntradayPeriod $approvedIntradayPeriod): bool
    {
        return $user->hasPermissionTo('approved_intraday_periods.update');
    }

    public function delete(User $user, ApprovedIntradayPeriod $approvedIntradayPeriod): bool
    {
        return $user->hasPermissionTo('approved_intraday_periods.delete');
    }
}
