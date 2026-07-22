<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Policies;

use App\Modules\CoreModule\Models\User;

class ReportPolicy
{
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('reports.export');
    }

    public function viewAll(User $user): bool
    {
        if ($user->hasRole(['admin', 'wfm', 'director', 'chief', 'coordinator'])) {
            return true;
        }

        return false;
    }

    public function viewTeam(User $user): bool
    {
        if ($user->hasRole(['admin', 'wfm', 'director', 'chief', 'coordinator', 'supervisor'])) {
            return $user->employee !== null;
        }

        return false;
    }
}
