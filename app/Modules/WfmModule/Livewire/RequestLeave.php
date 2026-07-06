<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Livewire\Forms\LeaveRequestForm;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WorkflowsModule\Actions\CreateLeaveRequestAction;
use App\Modules\WorkflowsModule\DTOs\CreateLeaveRequestDTO;
use App\Modules\WorkflowsModule\Models\LeaveRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RequestLeave extends Component
{
    public LeaveRequestForm $form;

    public int $availableMinutes = 0;

    public int $usedMinutes = 0;

    public function mount($type = 'quarterly')
    {
        $this->form->type = $type;
        $this->form->date = now()->format('Y-m-d');
        $this->calculateBalance();
    }

    public function updatedFormType()
    {
        $this->calculateBalance();
    }

    public function calculateBalance()
    {
        $employee = Auth::user()->employee;
        if (! $employee) {
            return;
        }

        if ($this->form->type === 'quarterly') {
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
        $this->form->validate();

        $employee = Auth::user()->employee;
        if (! $employee) {
            return;
        }

        $start = Carbon::parse($this->form->date.' '.($this->form->isFullDay ? '00:00' : $this->form->startTime));
        $end = Carbon::parse($this->form->date.' '.($this->form->isFullDay ? '23:59' : $this->form->endTime));

        if ($this->form->isFullDay) {
            $assignment = $this->getAssignment($this->form->date);
            if (! $assignment) {
                $this->addError('form.date', 'No tienes un turno asignado para este día.');

                return;
            }
            $start = Carbon::parse($this->form->date.' '.$assignment->start_time->format('H:i:s'));
            $end = Carbon::parse($this->form->date.' '.$assignment->end_time->format('H:i:s'));
        }

        $requestedMinutes = (int) $start->diffInMinutes($end);

        if ($this->form->type === 'quarterly' && $requestedMinutes > $this->availableMinutes) {
            $hours = round($this->availableMinutes / 60, 1);
            $this->addError('general', "No tienes suficiente saldo trimestral. Saldo restante: {$hours} horas.");

            return;
        }

        if ($requestedMinutes <= 0) {
            $this->addError('form.endTime', 'La hora de fin debe ser posterior a la de inicio.');

            return;
        }

        $dto = new CreateLeaveRequestDTO(
            employeeId: (int) $employee->id,
            type: $this->form->type,
            startTime: $start,
            endTime: $end,
            minutes: $requestedMinutes,
            reason: $this->form->reason
        );

        $action->execute($dto, (int) auth()->id());

        $typeLabel = $this->form->type === 'quarterly' ? 'trimestral' : 'compensatorio';
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
