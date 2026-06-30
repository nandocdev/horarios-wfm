<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WorkflowsModule\Models\LeaveRequest;
use App\Modules\WorkflowsModule\Models\LeaveRequestApproval;
use App\Shared\Events\LeaveRequestDecision;
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

        $managedTeamIds = $manager->getManagedTeamIds();

        $leave = LeaveRequest::where('id', $leaveId)
            ->where('status', 'pending')
            ->where(function ($q) use ($manager, $managedTeamIds) {
                $q->whereIn('employee_id', $manager->getAllSubordinateIds())
                    ->orWhereHas('employee', fn ($sq) => $sq->whereIn('team_id', $managedTeamIds));
            })
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

        LeaveRequestDecision::dispatch($leave, 'approved', auth()->id(), $comment);

        \Flux::toast('Permiso aprobado correctamente.', variant: 'success');
    }

    public function rejectLeave($leaveId, $comment = 'Rechazado por Jefe Inmediato')
    {
        $manager = Auth::user()->employee;
        if (! $manager) {
            return;
        }

        $managedTeamIds = $manager->getManagedTeamIds();

        $leave = LeaveRequest::where('id', $leaveId)
            ->where('status', 'pending')
            ->where(function ($q) use ($manager, $managedTeamIds) {
                $q->whereIn('employee_id', $manager->getAllSubordinateIds())
                    ->orWhereHas('employee', fn ($sq) => $sq->whereIn('team_id', $managedTeamIds));
            })
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

        LeaveRequestDecision::dispatch($leave, 'rejected', auth()->id(), $comment);

        \Flux::toast('Permiso rechazado.');
    }

    public function render()
    {
        $manager = Auth::user()->employee;
        $requests = collect();

        if ($manager) {
            $subordinateIds = $manager->getAllSubordinateIds();
            $managedTeamIds = $manager->getManagedTeamIds();

            $query = LeaveRequest::with(['employee', 'employee.position', 'employee.team'])
                ->where('status', 'pending');

            if ($manager->hasCoordinatorRights()) {
                // Ve solicitudes de sus subordinados O de cualquier miembro de sus equipos gestionados
                $query->where(function ($q) use ($subordinateIds, $managedTeamIds) {
                    $q->whereIn('employee_id', $subordinateIds)
                        ->orWhereHas('employee', fn ($sq) => $sq->whereIn('team_id', $managedTeamIds));
                });
            } else {
                // Solo ve solicitudes de sus subordinados
                $query->whereIn('employee_id', $subordinateIds);
            }

            $requests = $query->orderBy('start_time', 'asc')->paginate(15);
        }

        return view('wfm::livewire.manager-approvals', [
            'requests' => $requests,
        ])->layout('layouts.app');
    }
}
