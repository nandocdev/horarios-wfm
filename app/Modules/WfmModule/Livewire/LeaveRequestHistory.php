<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\GenerateFormPdfAction;
use App\Modules\WfmModule\DTOs\AbsenceReportDTO;
use App\Modules\WfmModule\Enums\AbsenceReasonType;
use App\Modules\WfmModule\Models\LeaveRequest;
use Carbon\Carbon;
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

        $employee = $leave->employee;

        $report = new AbsenceReportDTO(
            employee_number: $employee?->employee_number ?? '__________',
            employee_name: $employee?->full_name ?? '',
            absence_start_date: Carbon::parse($leave->start_time),
            absence_total_days: max(1, (int) ceil($leave->minutes / 480)),
            employee_position: $employee?->position?->name ?? '__________',
            cip_number: $employee?->metadata['cip'] ?? '',
            base_salary: (float) ($employee?->salary ?? 0),
            salary_supplement: 0.0,
            is_justified: $leave->status === 'approved',
            reason_type: match ($leave->type) {
                'enfermedad' => AbsenceReasonType::CommonIllness,
                'compensatorio' => AbsenceReasonType::CommonIllness,
                'duelo' => AbsenceReasonType::Bereavement,
                'nacimiento' => AbsenceReasonType::ChildBirth,
                default => AbsenceReasonType::Other,
            },
            medical_certificate_attached: $leave->type === 'enfermedad',
            has_witnesses: false,
            observations: $leave->reason ?? '',
            department_head_name: $employee?->manager?->full_name ?? '____________________',
            executive_unit: $employee?->team?->name ?? '____________________',
            discount_code: null,
            discount_description: null,
            discount_amount: null,
            discount_balance: null,
            accountant_name: '____________________',
            discount_biweekly_authorized: false,
        );

        return $action->execute(
            data: ['report' => $report],
            view: 'pdf::forms.leave-request',
            title: 'Reporte de Inasistencia - '.$report->employee_number,
        );
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
