<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Notifications\SwapStatusChangedNotification;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Enums\NotificationType;
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
            'requester.employee.team',
            'recipient.employee.team',
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
            // requester_id/recipient_id son users.id; los turnos viven en employees.
            $requesterEmployeeId = $this->selectedRequest->requester?->employee?->getKey();
            $recipientEmployeeId = $this->selectedRequest->recipient?->employee?->getKey();

            $this->requesterShift = $requesterEmployeeId
                ? WeeklyScheduleAssignment::with('schedule')
                    ->where('weekly_schedule_id', $week->id)
                    ->where('employee_id', $requesterEmployeeId)
                    ->where('day_of_week', $dayOfWeek)
                    ->first()
                : null;

            $this->recipientShift = $recipientEmployeeId
                ? WeeklyScheduleAssignment::with('schedule')
                    ->where('weekly_schedule_id', $week->id)
                    ->where('employee_id', $recipientEmployeeId)
                    ->where('day_of_week', $dayOfWeek)
                    ->first()
                : null;
        }

        $this->dispatch('modal-show', name: 'swap-details');
    }

    public function cancelSwap($requestId)
    {
        $employee = Auth::user()->employee;
        $request = ShiftSwapRequest::where('id', $requestId)
            ->where('requester_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $request->update(['status' => 'cancelled']);

        ShiftSwapCancelled::dispatch($request, $employee->id);

        // Notificar al destinatario si el usuario existía
        if ($request->recipient) {
            $dto = new NotificationDTO(
                title: 'Solicitud de intercambio cancelada',
                message: "{$employee->full_name} ha cancelado la solicitud de intercambio para el {$request->start_date->format('d/m/Y')}.",
                summary: 'El solicitante ha cancelado la solicitud de intercambio.',
                actionUrl: route('schedules.swap-history', [], false),
                icon: 'x-circle',
                level: 'warning',
                notificationType: NotificationType::ShiftSwapCancelled->value,
                facts: [
                    ['label' => 'Periodo', 'value' => $request->start_date->format('d/m/Y')],
                    ['label' => 'Estado', 'value' => 'Cancelado'],
                ],
                resourceType: 'shift_swap',
                resourceId: (string) $request->id,
            );
            $request->recipient->notify(new SwapStatusChangedNotification($dto));
        }

        \Flux::toast('Solicitud cancelada correctamente.');
        $this->dispatch('modal-hide', name: 'swap-details');
    }

    public function acceptSwap($requestId)
    {
        $employee = Auth::user()->employee;
        $request = ShiftSwapRequest::with(['requester.employee.team', 'recipient.employee'])
            ->where('id', $requestId)
            ->where('recipient_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $request->update(['status' => 'accepted']);

        ShiftSwapAccepted::dispatch($request, $employee->id);

        $dateStr = $request->start_date->format('d/m/Y');

        // Notificar al solicitante
        if ($request->requester) {
            $dto = new NotificationDTO(
                title: 'Intercambio aceptado',
                message: "{$employee->full_name} ha aceptado tu solicitud de intercambio para el {$dateStr}.",
                summary: 'Tu solicitud de intercambio ha sido aceptada. Pendiente por aprobación de WFM.',
                actionUrl: route('schedules.swap-history', [], false),
                icon: 'check-circle',
                level: 'success',
                notificationType: NotificationType::ShiftSwapAccepted->value,
                facts: [
                    ['label' => 'Periodo', 'value' => $dateStr],
                    ['label' => 'Aceptado por', 'value' => $employee->full_name],
                    ['label' => 'Estado', 'value' => 'Aceptado — Pendiente de aprobación WFM'],
                ],
                recommendation: 'Espera la aprobación del equipo WFM.',
                resourceType: 'shift_swap',
                resourceId: (string) $request->id,
            );
            $request->requester->notify(new SwapStatusChangedNotification($dto));
        }

        // Notificar al coordinador del solicitante
        $this->notifyCoordinator($request->requester?->employee?->manager, $request->requester?->employee, $employee, $dateStr);

        // Notificar al coordinador del destinatario (quien aceptó)
        $this->notifyCoordinator($request->recipient?->employee?->manager, $request->recipient?->employee, $request->requester?->employee, $dateStr);

        \Flux::toast('Has aceptado el intercambio de turno.', variant: 'success');
        $this->dispatch('modal-hide', name: 'swap-details');
    }

    private function notifyCoordinator(?Employee $coordinator, $operator, $otherOperator, string $dateStr): void
    {
        if (! $coordinator?->user) {
            return;
        }

        $dto = new NotificationDTO(
            title: 'Intercambio aceptado — pendiente de aprobación',
            message: "{$operator->full_name} y {$otherOperator->full_name} han acordado un intercambio para el {$dateStr}.",
            summary: "{$operator->full_name} y {$otherOperator->full_name} acordaron un intercambio. Requiere aprobación de WFM.",
            actionUrl: route('schedules.swap-history', [], false),
            icon: 'arrows-right-left',
            level: 'info',
            notificationType: NotificationType::ShiftSwapAccepted->value,
            facts: [
                ['label' => 'Periodo', 'value' => $dateStr],
                ['label' => 'Participantes', 'value' => "{$operator->full_name}, {$otherOperator->full_name}"],
                ['label' => 'Estado', 'value' => 'Pendiente de aprobación WFM'],
            ],
            recommendation: 'Revisa y aprueba o rechaza la solicitud.',
            resourceType: 'shift_swap',
            resourceId: (string) $operator->id,
        );
        $coordinator->user->notify(new SwapStatusChangedNotification($dto));
    }

    public function rejectSwap($requestId)
    {
        $employee = Auth::user()->employee;
        $request = ShiftSwapRequest::with(['requester'])->where('id', $requestId)
            ->where('recipient_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $request->update(['status' => 'rejected']);

        ShiftSwapRejectedByPeer::dispatch($request, $employee->id);

        // Notificar al solicitante
        if ($request->requester) {
            $dto = new NotificationDTO(
                title: 'Intercambio rechazado',
                message: "{$employee->full_name} ha rechazado tu solicitud de intercambio para el {$request->start_date->format('d/m/Y')}.",
                summary: 'Tu solicitud de intercambio ha sido rechazada por el destinatario.',
                actionUrl: route('schedules.swap-history', [], false),
                icon: 'x-circle',
                level: 'danger',
                notificationType: NotificationType::ShiftSwapRejected->value,
                facts: [
                    ['label' => 'Periodo', 'value' => $request->start_date->format('d/m/Y')],
                    ['label' => 'Estado', 'value' => 'Rechazado'],
                ],
                recommendation: 'Puedes crear una nueva solicitud para otra fecha.',
                resourceType: 'shift_swap',
                resourceId: (string) $request->id,
            );
            $request->requester->notify(new SwapStatusChangedNotification($dto));
        }

        \Flux::toast('Has rechazado el intercambio de turno.');
        $this->dispatch('modal-hide', name: 'swap-details');
    }

    public function render()
    {
        $employee = Auth::user()->employee;

        $requests = $employee
            ? ShiftSwapRequest::with(['requester.employee.team', 'recipient.employee.team'])
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
