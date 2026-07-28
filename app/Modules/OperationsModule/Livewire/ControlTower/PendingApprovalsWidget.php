<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class PendingApprovalsWidget extends Component
{
    public function placeholder()
    {
        return '<div class="h-24 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function render(DashboardScheduleQueriesInterface $scheduleQueries)
    {
        $pendingLeaves = LeaveRequest::whereIn('status', ['pending', 'pendiente'])->count();
        $pendingSwaps = ShiftSwapRequest::where('status', 'pending')->count();
        $pendingApprovals = $pendingLeaves + $pendingSwaps;

        $today = now()->toDateString();
        $leaveCounts = $scheduleQueries->getLeaveCounts(null, $today);

        return view('operations::livewire.control-tower.pending-approvals-widget', [
            'pendingApprovals' => $pendingApprovals,
            'pendingLeaves' => $pendingLeaves,
            'pendingSwaps' => $pendingSwaps,
            'approvedLeaves' => $leaveCounts['approved'] ?? 0,
            'vacations' => ScheduleException::whereDate('start_at', '<=', $today)
                ->whereDate('end_at', '>=', $today)
                ->whereHas('reason', fn ($q) => $q->where('short_code', 'V.'))
                ->count(),
        ]);
    }
}
