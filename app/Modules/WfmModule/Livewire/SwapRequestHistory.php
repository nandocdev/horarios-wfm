<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Notifications\SwapStatusChangedNotification;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Events\ShiftSwapAccepted;
use App\Shared\Events\ShiftSwapCancelled;
use App\Shared\Events\ShiftSwapRejectedByPeer;
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

        ShiftSwapCancelled::dispatch($request, $employee->id);

        // Notificar al destinatario si el usuario existía
        if ($request->recipient?->user) {
            $dto = new NotificationDTO(
                title: 'Solicitud Cancelada',
                message: "{$employee->full_name} ha cancelado la solicitud de intercambio para el {$request->start_date->format('d/m/Y')}.",
                actionUrl: route('schedules.swap-history', [], false),
                level: 'warning'
            );
            $request->recipient->user->notify(new SwapStatusChangedNotification($dto));
        }

        \Flux::toast('Solicitud cancelada correctamente.');
        $this->dispatch('modal-hide', name: 'swap-details');
    }

    public function acceptSwap($requestId)
    {
        $employee = Auth::user()->employee;
        $request = ShiftSwapRequest::with([
            'requester', 'requester.user', 'requester.manager.user',
            'recipient', 'recipient.user', 'recipient.manager.user',
        ])->where('id', $requestId)
            ->where('recipient_id', $employee->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $request->update(['status' => 'accepted']);

        ShiftSwapAccepted::dispatch($request, $employee->id);

        $dateStr = $request->start_date->format('d/m/Y');

        // Notificar al solicitante
        if ($request->requester?->user) {
            $dto = new NotificationDTO(
                title: 'Intercambio Aceptado',
                message: "{$employee->full_name} ha aceptado tu solicitud de intercambio para el {$dateStr}. Pendiente por aprobación de WFM.",
                actionUrl: route('schedules.swap-history', [], false),
                level: 'success'
            );
            $request->requester->user->notify(new SwapStatusChangedNotification($dto));
        }

        // Notificar al coordinador del solicitante
        $this->notifyCoordinator($request->requester?->manager, $request->requester, $employee, $dateStr);

        // Notificar al coordinador del destinatario (quien aceptó)
        $this->notifyCoordinator($request->recipient?->manager, $request->recipient, $request->requester, $dateStr);

        \Flux::toast('Has aceptado el intercambio de turno.', variant: 'success');
        $this->dispatch('modal-hide', name: 'swap-details');
    }

    private function notifyCoordinator(?Employee $coordinator, $operator, $otherOperator, string $dateStr): void
    {
        if (! $coordinator?->user) {
            return;
        }

        $dto = new NotificationDTO(
            title: 'Intercambio de Turno Aceptado — Pendiente de Aprobación',
            message: "{$operator->full_name} y {$otherOperator->full_name} han acordado un intercambio para el {$dateStr}. Requiere aprobación de WFM.",
            actionUrl: route('schedules.swap-history', [], false),
            level: 'info'
        );
        $coordinator->user->notify(new SwapStatusChangedNotification($dto));
    }

    public function rejectSwap($requestId)
    {
        $employee = Auth::user()->employee;
        $request = ShiftSwapRequest::with(['requester', 'requester.user'])->where('id', $requestId)
            ->where('recipient_id', $employee->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $request->update(['status' => 'rejected']);

        ShiftSwapRejectedByPeer::dispatch($request, $employee->id);

        // Notificar al solicitante
        if ($request->requester?->user) {
            $dto = new NotificationDTO(
                title: 'Intercambio Rechazado',
                message: "{$employee->full_name} ha rechazado tu solicitud de intercambio para el {$request->start_date->format('d/m/Y')}.",
                actionUrl: route('schedules.swap-history', [], false),
                level: 'danger'
            );
            $request->requester->user->notify(new SwapStatusChangedNotification($dto));
        }

        \Flux::toast('Has rechazado el intercambio de turno.');
        $this->dispatch('modal-hide', name: 'swap-details');
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
