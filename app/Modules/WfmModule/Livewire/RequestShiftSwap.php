<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Notifications\SwapRequestNotification;
use App\Shared\Events\ShiftSwapRequested;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Component;

class RequestShiftSwap extends Component
{
    #[Rule('required|date|after:today')]
    public $requestedDate;

    #[Rule('nullable|date|after_or_equal:requestedDate')]
    public $endDate;

    #[Rule('required|exists:employees,id')]
    public $recipientId;

    #[Rule('nullable|string|max:255')]
    public $reason;

    // Propiedades para mostrar los horarios en la vista
    public $requesterAssignment = null;

    public $recipientAssignment = null;

    public function mount()
    {
        // Por defecto, sugerir mañana
        $this->requestedDate = now()->addDay()->format('Y-m-d');
        $this->endDate = now()->addDay()->format('Y-m-d');
        $this->loadAssignments();
    }

    public function updatedRequestedDate()
    {
        if (! $this->endDate || Carbon::parse($this->endDate)->lt(Carbon::parse($this->requestedDate))) {
            $this->endDate = $this->requestedDate;
        }
        $this->loadAssignments();
    }

    public function updatedEndDate()
    {
        $this->loadAssignments();
    }

    public function updatedRecipientId()
    {
        $this->loadAssignments();
    }

    public function loadAssignments()
    {
        $requester = Auth::user()->employee;
        if (! $requester || ! $this->requestedDate) {
            return;
        }

        $date = Carbon::parse($this->requestedDate);
        $dayOfWeek = $date->dayOfWeekIso;

        // Buscar la semana que contiene la fecha (independientemente del estado para permitir pruebas)
        $week = WeeklySchedule::whereDate('week_start_date', '<=', $date->toDateString())
            ->whereDate('week_end_date', '>=', $date->toDateString())
            ->first();

        if (! $week) {
            $this->requesterAssignment = null;
            $this->recipientAssignment = null;

            return;
        }

        $this->requesterAssignment = WeeklyScheduleAssignment::with('schedule')
            ->where('weekly_schedule_id', $week->id)
            ->where('employee_id', $requester->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if ($this->recipientId) {
            $this->recipientAssignment = WeeklyScheduleAssignment::with('schedule')
                ->where('weekly_schedule_id', $week->id)
                ->where('employee_id', $this->recipientId)
                ->where('day_of_week', $dayOfWeek)
                ->first();
        } else {
            $this->recipientAssignment = null;
        }
    }

    public function submit()
    {
        $this->validate();

        $requester = Auth::user()->employee;

        if (! $requester) {
            $this->addError('general', 'No tienes un perfil de empleado asociado.');

            return;
        }

        $recipient = Employee::with('position')->find($this->recipientId);

        // 1. Validar que tenga el mismo cargo
        if ($recipient->position_id !== $requester->position_id) {
            $this->addError('recipientId', "Solo puedes solicitar cambios de turno a compañeros con tu mismo cargo ({$requester->position?->name}).");

            return;
        }

        // 2. Validar que los turnos existan y sean distintos
        if (! $this->requesterAssignment || ! $this->recipientAssignment) {
            $this->addError('general', 'Ambos empleados deben tener turnos asignados ese día para realizar un intercambio (swap).');

            return;
        }

        if ($this->requesterAssignment->schedule_id === $this->recipientAssignment->schedule_id) {
            $this->addError('recipientId', 'No puedes realizar un swap con un compañero que tiene tu mismo turno.');

            return;
        }

        $swapRequest = ShiftSwapRequest::create([
            'requester_id' => $requester->id,
            'recipient_id' => $this->recipientId,
            'start_date' => $this->requestedDate,
            'end_date' => $this->endDate ?: $this->requestedDate,
            'status' => 'pending',
            'reason' => $this->reason,
            'requester_assignment_snapshot' => $this->requesterAssignment->toArray(),
            'recipient_assignment_snapshot' => $this->recipientAssignment->toArray(),
        ]);

        ShiftSwapRequested::dispatch($swapRequest);

        if ($recipient->user) {
            $recipient->user->notify(new SwapRequestNotification($swapRequest));
        }

        \Flux::toast('Solicitud de cambio de turno enviada correctamente.');

        return redirect()->route('schedules.swap-history');
    }

    public function render()
    {
        $currentEmployee = Auth::user()->employee;
        $peers = collect();

        if ($currentEmployee && $this->requestedDate) {
            $date = Carbon::parse($this->requestedDate);
            $dayOfWeek = $date->dayOfWeekIso;

            // Asegurar que tenemos el horario cargado
            $this->loadAssignments();

            if ($this->requesterAssignment) {
                $peers = Employee::with(['team', 'position'])
                    ->where('position_id', $currentEmployee->position_id)
                    ->where('id', '!=', $currentEmployee->id)
                    // 1. Debe tener un turno asignado ese día en la misma semana
                    ->whereHas('assignments', function ($query) use ($dayOfWeek, $date) {
                        $query->where('day_of_week', $dayOfWeek)
                            ->whereHas('weeklySchedule', function ($q) use ($date) {
                                $q->whereDate('week_start_date', '<=', $date->toDateString())
                                    ->whereDate('week_end_date', '>=', $date->toDateString());
                            });
                    })
                    // 2. Ese turno debe ser distinto al del solicitante
                    ->whereDoesntHave('assignments', function ($query) use ($dayOfWeek, $date) {
                        $query->where('day_of_week', $dayOfWeek)
                            ->where('schedule_id', $this->requesterAssignment->schedule_id)
                            ->whereHas('weeklySchedule', function ($q) use ($date) {
                                $q->whereDate('week_start_date', '<=', $date->toDateString())
                                    ->whereDate('week_end_date', '>=', $date->toDateString());
                            });
                    })
                    ->get();
            }
        }

        return view('wfm::livewire.request-shift-swap', [
            'peers' => $peers,
        ])->layout('layouts.app');
    }
}
