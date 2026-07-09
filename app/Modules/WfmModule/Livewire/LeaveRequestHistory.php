<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class LeaveRequestHistory extends Component
{
    use WithPagination;

    public $selectedLeave = null;

    public function showDetails($leaveId)
    {
        $this->selectedLeave = LeaveRequest::with([
            'approvals',
            'approvals.approver',
        ])->findOrFail($leaveId);

        $this->dispatch('modal-show', name: 'leave-details');
    }

    public function cancelLeave($leaveId)
    {
        $employee = Auth::user()->employee;
        $leave = LeaveRequest::where('id', $leaveId)
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $leave->update(['status' => 'cancelled']);
        session()->flash('status', 'Solicitud de permiso cancelada.');
    }

    public function render()
    {
        $employee = Auth::user()->employee;

        $leaves = $employee
            ? LeaveRequest::where('employee_id', $employee->id)
                ->orderBy('start_time', 'desc')
                ->paginate(10)
            : collect();

        return view('wfm::livewire.leave-request-history', [
            'leaves' => $leaves,
        ])->layout('layouts.app');
    }
}
