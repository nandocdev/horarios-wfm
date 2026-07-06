<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WorkflowsModule\Actions\CreateLeaveRequestAction;
use App\Modules\WorkflowsModule\DTOs\CreateLeaveRequestDTO;
use App\Modules\WorkflowsModule\Models\LeaveRequest;
use App\Shared\Events\LeaveRequestCreated;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RequestLeave extends Component
{
    public $type; // 'quarterly' o 'compensatory'

    public $date;

    public $startTime;

    public $endTime;

    public $reason;

    public $isFullDay = false;

    public $availableMinutes = 0;

    public $usedMinutes = 0;

    protected $rules = [
        'date' => 'required|date|after_or_equal:today',
        'startTime' => 'required_if:isFullDay,false',
        'endTime' => 'required_if:isFullDay,false',
        'reason' => 'required|string|min:10',
    ];

    public function mount($type = 'quarterly')
    {
        $this->type = $type;
        $this->date = now()->format('Y-m-d');
        $this->calculateBalance();
    }

    public function updatedType()
    {
        $this->calculateBalance();
    }

    public function calculateBalance()
    {
        $employee = Auth::user()->employee;
        if (! $employee) {
            return;
        }

        if ($this->type === 'quarterly') {
            $startOfQuarter = now()->startOfQuarter();
            $endOfQuarter = now()->endOfQuarter();

            $this->usedMinutes = LeaveRequest::where('employee_id', $employee->id)
                ->where('type', 'quarterly')
                ->whereIn('status', ['pending', 'approved'])
                ->whereBetween('start_time', [$startOfQuarter, $endOfQuarter])
                ->sum('minutes');

            $this->availableMinutes = max(0, 480 - $this->usedMinutes);
        } else {
            // Lógica para Compensatorio (Por ahora permitimos, en futuro consultar bolsa de horas extra)
            // Supongamos que por ahora no hay límite estricto o es manual
            $this->availableMinutes = 9999;
            $this->usedMinutes = 0;
        }
    }

    public function submit(CreateLeaveRequestAction $action)
    {
        $this->validate();

        $employee = Auth::user()->employee;
        if (! $employee) {
            return;
        }

        $start = Carbon::parse($this->date.' '.($this->isFullDay ? '00:00' : $this->startTime));
        $end = Carbon::parse($this->date.' '.($this->isFullDay ? '23:59' : $this->endTime));

        if ($this->isFullDay) {
            $assignment = $this->getAssignment($this->date);
            if (! $assignment) {
                $this->addError('date', 'No tienes un turno asignado para este día.');

                return;
            }
            $start = Carbon::parse($this->date.' '.$assignment->start_time->format('H:i:s'));
            $end = Carbon::parse($this->date.' '.$assignment->end_time->format('H:i:s'));
        }

        $requestedMinutes = (int) $start->diffInMinutes($end);

        if ($this->type === 'quarterly' && $requestedMinutes > $this->availableMinutes) {
            $hours = round($this->availableMinutes / 60, 1);
            $this->addError('general', "No tienes suficiente saldo trimestral. Saldo restante: {$hours} horas.");

            return;
        }

        if ($requestedMinutes <= 0) {
            $this->addError('endTime', 'La hora de fin debe ser posterior a la de inicio.');

            return;
        }

        $dto = new CreateLeaveRequestDTO(
            employeeId: (int) $employee->id,
            type: $this->type,
            startTime: $start,
            endTime: $end,
            minutes: $requestedMinutes,
            reason: $this->reason
        );

        $action->execute($dto, (int) auth()->id());

        $typeLabel = $this->type === 'quarterly' ? 'trimestral' : 'compensatorio';
        \Flux::toast("Solicitud de permiso {$typeLabel} enviada al jefe inmediato.");

        $this->redirect(route('schedules.leave-history'), navigate: true);
    }

    protected function getAssignment($dateString)
    {
        $employee = Auth::user()->employee;
        $date = Carbon::parse($dateString);

        $week = WeeklySchedule::whereDate('week_start_date', '<=', $date->toDateString())
            ->whereDate('week_end_date', '>=', $date->toDateString())
            ->first();

        if (! $week) {
            return null;
        }

        return WeeklyScheduleAssignment::where('weekly_schedule_id', $week->id)
            ->where('employee_id', $employee->id)
            ->where('day_of_week', $date->dayOfWeekIso)
            ->first();
    }

    public function render()
    {
        return view('wfm::livewire.request-leave')->layout('layouts.app');
    }
}
