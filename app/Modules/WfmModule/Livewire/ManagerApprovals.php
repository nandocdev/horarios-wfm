<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WorkflowsModule\Models\LeaveRequest;
use App\Modules\WorkflowsModule\Models\LeaveRequestApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ManagerApprovals extends Component
{
    use WithPagination;

    public function approveLeave($leaveId, $comment = 'Aprobado por Jefe Inmediato')
    {
        $manager = Auth::user()->employee;
        if (! $manager) {
            return;
        }

        $subordinateIds = $manager->getAllSubordinateIds();

        $leave = LeaveRequest::where('id', $leaveId)
            ->whereIn('employee_id', $subordinateIds)
            ->where('status', 'pending')
            ->firstOrFail();

        DB::transaction(function () use ($leave, $manager, $comment) {
            LeaveRequestApproval::create([
                'leave_request_id' => $leave->id,
                'approver_id' => $manager->id,
                'status' => 'approved',
                'comment' => $comment,
                'step_order' => 1,
            ]);

            $leave->update(['status' => 'approved']);
        });

        \Flux::toast('Permiso aprobado correctamente.', variant: 'success');
    }

    public function rejectLeave($leaveId, $comment = 'Rechazado por Jefe Inmediato')
    {
        $manager = Auth::user()->employee;
        if (! $manager) {
            return;
        }

        $subordinateIds = $manager->getAllSubordinateIds();

        $leave = LeaveRequest::where('id', $leaveId)
            ->whereIn('employee_id', $subordinateIds)
            ->where('status', 'pending')
            ->firstOrFail();

        DB::transaction(function () use ($leave, $manager, $comment) {
            LeaveRequestApproval::create([
                'leave_request_id' => $leave->id,
                'approver_id' => $manager->id,
                'status' => 'rejected',
                'comment' => $comment,
                'step_order' => 1,
            ]);

            $leave->update(['status' => 'rejected']);
        });

        \Flux::toast('Permiso rechazado.');
    }

    public function render()
    {
        $manager = Auth::user()->employee;
        $requests = collect();

        if ($manager) {
            $subordinateIds = $manager->getAllSubordinateIds();

            $requests = LeaveRequest::with(['employee', 'employee.position', 'employee.team'])
                ->whereIn('employee_id', $subordinateIds)
                ->where('status', 'pending')
                ->orderBy('start_time', 'asc')
                ->paginate(15);
        }

        return view('wfm::livewire.manager-approvals', [
            'requests' => $requests,
        ])->layout('layouts.app');
    }
}
