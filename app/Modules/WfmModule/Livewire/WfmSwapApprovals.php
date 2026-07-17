<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\ApproveShiftSwapAction;
use App\Modules\WfmModule\Actions\RejectShiftSwapAction;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Notifications\SwapStatusChangedNotification;
use App\Shared\DTOs\NotificationDTO;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class WfmSwapApprovals extends Component
{
    use WithPagination;

    public string $currentTab = 'pending';

    public $selectedRequest = null;

    public $requesterShift = null;

    public $recipientShift = null;

    public function updatedCurrentTab(): void
    {
        $this->resetPage();
    }

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

        $date = Carbon::parse($this->selectedRequest->start_date);
        $dayOfWeek = $date->dayOfWeekIso;

        $week = WeeklySchedule::where('week_start_date', '<=', $date->toDateString())
            ->where('week_end_date', '>=', $date->toDateString())
            ->first();

        $this->requesterShift = null;
        $this->recipientShift = null;

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

    public function approveSwap($requestId)
    {
        $this->authorize('wfm.swaps.manage');

        try {
            $employee = Auth::user()->employee;
            if (! $employee) {
                throw new \RuntimeException('El usuario autenticado debe tener un perfil de empleado asociado para aprobar solicitudes.');
            }

            $action = app(ApproveShiftSwapAction::class);
            $action->execute((int) $requestId, $employee->id);

            \Flux::toast('Cambio de turno aprobado y aplicado correctamente.', variant: 'success');
            $this->dispatch('modal-hide', name: 'swap-details');
            $this->selectedRequest = null;
        } catch (\Throwable $e) {
            \Flux::toast('Error al aprobar: '.$e->getMessage(), variant: 'danger');
            $this->dispatch('modal-hide', name: 'swap-details');
            $this->selectedRequest = null;
        }
    }

    public function rejectSwap($requestId, $reason = 'Rechazado por WFM')
    {
        $this->authorize('wfm.swaps.manage');

        try {
            $employee = Auth::user()->employee;
            if (! $employee) {
                throw new \RuntimeException('El usuario autenticado debe tener un perfil de empleado asociado para rechazar solicitudes.');
            }

            $action = app(RejectShiftSwapAction::class);
            $request = $action->execute((int) $requestId, $employee->id, $reason);

            $dto = new NotificationDTO(
                title: 'Intercambio de Turno Rechazado',
                message: "Tu solicitud de intercambio para el {$request->start_date->format('d/m/Y')} ha sido rechazada por el supervisor. Motivo: {$reason}",
                actionUrl: route('schedules.swap-history'),
                level: 'danger'
            );

            if ($request->requester?->user) {
                $request->requester->user->notify(new SwapStatusChangedNotification($dto));
            }

            if ($request->recipient?->user) {
                $request->recipient->user->notify(new SwapStatusChangedNotification($dto));
            }

            \Flux::toast('Cambio de turno rechazado.');
            $this->dispatch('modal-hide', name: 'swap-details');
            $this->selectedRequest = null;
        } catch (\Throwable $e) {
            \Flux::toast('Error al rechazar: '.$e->getMessage(), variant: 'danger');
            $this->dispatch('modal-hide', name: 'swap-details');
            $this->selectedRequest = null;
        }
    }

    public function render()
    {
        $query = ShiftSwapRequest::with(['requester', 'recipient', 'requester.team', 'recipient.team']);

        if ($this->currentTab === 'pending') {
            $query->whereIn('status', ['pending', 'accepted'])
                ->orderByRaw("CASE WHEN status = 'accepted' THEN 0 ELSE 1 END")
                ->orderBy('start_date', 'asc');
        } else {
            $query->whereIn('status', ['approved', 'rejected', 'cancelled'])
                ->orderBy('updated_at', 'desc');
        }

        $requests = $query->paginate(15);

        return view('wfm::livewire.wfm-swap-approvals', [
            'requests' => $requests,
        ])->layout('layouts.app');
    }
}
