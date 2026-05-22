<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WorkflowsModule\Models\ShiftSwapRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class SwapRequestHistory extends Component
{
    use WithPagination;

    public $selectedRequest = null;

    public $requesterShift = null;

    public $recipientShift = null;

    public function showDetails($requestId)
    {
        $this->selectedRequest = ShiftSwapRequest::with([
            'requester',
            'recipient',
            'requester.team',
            'recipient.team',
            'approvals',
            'approvals.approver',
        ])->findOrFail($requestId);

        // Cargar horarios para el detalle
        $date = Carbon::parse($this->selectedRequest->start_date);
        $dayOfWeek = $date->dayOfWeekIso;

        $week = WeeklySchedule::where('week_start_date', '<=', $date->toDateString())
            ->where('week_end_date', '>=', $date->toDateString())
            ->first();

        if ($week) {
            $this->requesterShift = WeeklyScheduleAssignment::with('schedule')
                ->where('weekly_schedule_id', $week->id)
                ->where('employee_id', $this->selectedRequest->requester_id)
                ->where('day_of_week', $dayOfWeek)
                ->first();

            $this->recipientShift = WeeklyScheduleAssignment::with('schedule')
                ->where('weekly_schedule_id', $week->id)
                ->where('employee_id', $this->selectedRequest->recipient_id)
                ->where('day_of_week', $dayOfWeek)
                ->first();
        }

        $this->dispatch('modal-show', name: 'swap-details');
    }

    public function cancelSwap($requestId)
    {
        $employee = Auth::user()->employee;
        $request = ShiftSwapRequest::where('id', $requestId)
            ->where('requester_id', $employee->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $request->update(['status' => 'cancelled']);
        \Flux::toast('Solicitud cancelada correctamente.');
    }

    public function acceptSwap($requestId)
    {
        $employee = Auth::user()->employee;
        $request = ShiftSwapRequest::where('id', $requestId)
            ->where('recipient_id', $employee->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $request->update(['status' => 'accepted']);
        \Flux::toast('Has aceptado el intercambio de turno.', variant: 'success');
    }

    public function rejectSwap($requestId)
    {
        $employee = Auth::user()->employee;
        $request = ShiftSwapRequest::where('id', $requestId)
            ->where('recipient_id', $employee->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $request->update(['status' => 'rejected']);
        \Flux::toast('Has rechazado el intercambio de turno.');
    }

    public function render()
    {
        $employee = Auth::user()->employee;

        $requests = $employee
            ? ShiftSwapRequest::with(['requester', 'recipient', 'recipient.team', 'requester.team'])
                ->where(function ($query) use ($employee) {
                    $query->where('requester_id', $employee->id)
                        ->orWhere('recipient_id', $employee->id);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10)
            : collect();

        return view('wfm::livewire.swap-request-history', [
            'requests' => $requests,
            'currentEmployeeId' => $employee?->id,
        ])->layout('layouts.app');
    }
}
