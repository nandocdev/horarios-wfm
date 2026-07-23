<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\GenerateFormPdfAction;
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

    public function downloadPdf(int $leaveId, GenerateFormPdfAction $action)
    {
        $leave = LeaveRequest::with('employee')->findOrFail($leaveId);

        $data = [
            'days' => round($leave->minutes / 480, 1),
            'employeeName' => $leave->employee?->full_name ?? '—',
            'employeeNumber' => $leave->employee?->employee_number ?? '—',
            'position' => $leave->employee?->position?->name ?? '—',
            'salary' => $leave->employee?->salary ?? '—',
            'bonus' => '',
            'isJustified' => $leave->status === 'approved',
            'reason' => match ($leave->type) {
                'enfermedad' => 'enfermedad',
                'compensatorio' => 'comun',
                default => 'otro',
            },
            'hasCertificate' => $leave->type === 'enfermedad',
            'hasDocuments' => true,
            'hasRestCertificate' => false,
            'observations' => $leave->reason,
            'date' => $leave->created_at?->format('d/m/Y') ?? now()->format('d/m/Y'),
        ];

        return $action->execute($data, 'pdf::forms.leave-request', 'Formulario_Inasistencia');
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
