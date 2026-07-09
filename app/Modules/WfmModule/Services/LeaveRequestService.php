<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Services;

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Shared\Contracts\Schedules\LeaveRequestServiceInterface;

final class LeaveRequestService implements LeaveRequestServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPendingCountForUser(int $userId): int
    {
        $user = User::find($userId);
        if (! $user || ! $user->employee) {
            return 0;
        }

        $managedIds = $user->employee->getAllSubordinateIds();
        if (empty($managedIds)) {
            if ($user->can('wfm.leaves.manage')) {
                return LeaveRequest::where('status', 'pending')->count();
            }

            return 0;
        }

        return LeaveRequest::whereIn('employee_id', $managedIds)
            ->where('status', 'pending')
            ->count();
    }
}
