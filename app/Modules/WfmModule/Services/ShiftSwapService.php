<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Services;

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Shared\Contracts\Schedules\ShiftSwapServiceInterface;

final class ShiftSwapService implements ShiftSwapServiceInterface
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
                return ShiftSwapRequest::where('status', 'pending')->count();
            }

            return 0;
        }

        // requester_id almacena users.id; los subordinados son employees.id.
        $managedUserIds = Employee::whereIn('id', $managedIds)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->all();

        if (empty($managedUserIds)) {
            return 0;
        }

        return ShiftSwapRequest::whereIn('requester_id', $managedUserIds)
            ->where('status', 'pending')
            ->count();
    }
}
