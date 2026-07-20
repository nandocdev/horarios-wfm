<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Actions\DeleteScheduleExceptionAction;
use App\Modules\WfmModule\Actions\SaveScheduleExceptionAction;
use App\Modules\WfmModule\Livewire\Forms\IncidentForm;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyTeam extends Component
{
    use WithPagination;

    public string $date;

    public $weekStart;

    public $weekEnd;

    public $selectedTeam = null;

    public bool $showIncidentModal = false;

    public IncidentForm $incidentForm;

    public function mount(): void
    {
        $this->date = Carbon::now()->format('Y-m-d');
        $this->weekStart = Carbon::now()->startOfWeek();
        $this->weekEnd = Carbon::now()->endOfWeek();

        $employee = Auth::user()->employee;
        if (! $employee) {
            abort(403, 'No tienes un perfil de empleado asociado.');
        }

        $isPowerUser = Auth::user()->hasAnyRole(['admin', 'wfm', 'superuser', 'chief']);

        if ($this->selectedTeam && ! $isPowerUser) {
            $managedTeamIds = $employee->getManagedTeamIds();
            if (! in_array($this->selectedTeam, $managedTeamIds)) {
                $this->selectedTeam = null;
            }
        }

        if ($this->selectedTeam === null && $employee->team_id && ! $isPowerUser) {
            $this->selectedTeam = $employee->team_id;
        }
    }

    public function updatedDate(): void
    {
        $this->weekStart = Carbon::parse($this->date)->startOfWeek();
        $this->weekEnd = Carbon::parse($this->date)->endOfWeek();
    }

    public function openIncidentModal($employeeId, $date): void
    {
        $this->incidentForm->resetForCreate((int) $employeeId, $date);
        $this->showIncidentModal = true;
        $this->dispatch('modal-show', name: 'incident-modal');
    }

    public function editIncident(int $id): void
    {
        $exception = ScheduleException::findOrFail($id);

        $this->incidentForm->fillForEdit(
            id: $exception->id,
            employeeId: $exception->employee_id,
            date: $exception->start_at->format('Y-m-d'),
            reasonId: $exception->absence_reason_code_id,
            startTime: $exception->start_at->format('H:i'),
            endTime: $exception->end_at->format('H:i'),
            isFullDay: (bool) $exception->is_full_day,
            remarks: $exception->remarks,
        );

        $this->showIncidentModal = true;
        $this->dispatch('modal-show', name: 'incident-modal');
    }

    public function saveIncident(SaveScheduleExceptionAction $action): void
    {
        $this->incidentForm->validate();

        $action->execute(
            employeeId: $this->incidentForm->employee_id,
            date: $this->incidentForm->date,
            reasonId: $this->incidentForm->reason_id,
            isFullDay: $this->incidentForm->is_full_day,
            startTime: $this->incidentForm->start_time,
            endTime: $this->incidentForm->end_time,
            remarks: $this->incidentForm->remarks,
            createdBy: (int) Auth::id(),
            exceptionId: $this->incidentForm->id,
        );

        $this->showIncidentModal = false;
        $this->dispatch('modal-close', name: 'incident-modal');

        \Flux::toast(
            $this->incidentForm->id
                ? __('Incidente actualizado correctamente.')
                : __('Incidente registrado correctamente.')
        );
    }

    public function deleteIncident(?int $id, DeleteScheduleExceptionAction $action): void
    {
        $id = $id ?: $this->incidentForm->id;

        if ($id) {
            $action->execute($id);
            $this->showIncidentModal = false;
            $this->dispatch('modal-close', name: 'incident-modal');
            \Flux::toast(__('Incidente eliminado correctamente.'), variant: 'warning');
        }
    }

    public function render()
    {
        $employee = Auth::user()->employee;
        $user = Auth::user();
        $isPowerUser = $user->hasAnyRole(['admin', 'wfm', 'superuser', 'chief']);

        $availableTeams = $isPowerUser
            ? Team::with('supervisor')->active()->get()
            : Team::with('supervisor')->whereIn('id', $employee->getManagedTeamIds())->get();

        $subordinateIds = $isPowerUser ? [] : $employee->getAllSubordinateIds();

        $query = Employee::query()->active();

        if ($this->selectedTeam) {
            $team = Team::find($this->selectedTeam);
            $teamMemberIds = $team->users()->pluck('employees.id')->toArray();

            if ($isPowerUser || $employee->hasCoordinatorRights() || $team->supervisor_id === $employee->id) {
                $query->whereIn('id', $teamMemberIds);
            } else {
                $query->whereIn('id', array_intersect($teamMemberIds, $subordinateIds));
            }
        } elseif (! $isPowerUser) {
            $managedTeamMemberIds = Employee::whereIn('team_id', $employee->getManagedTeamIds())->pluck('id')->toArray();
            $allVisibleIds = array_unique(array_merge($subordinateIds, $managedTeamMemberIds));
            $query->whereIn('id', $allVisibleIds);
        }

        $members = $query->with(['position'])->orderBy('first_name')->get();
        $memberIds = $members->pluck('id')->toArray();

        $weeklySchedule = WeeklySchedule::where('week_start_date', $this->weekStart->format('Y-m-d'))->first();

        $assignments = collect();
        if ($weeklySchedule) {
            $assignments = WeeklyScheduleAssignment::with(['schedule'])
                ->whereIn('employee_id', $memberIds)
                ->where('weekly_schedule_id', $weeklySchedule->id)
                ->get()
                ->groupBy('employee_id');
        }

        $exceptions = ScheduleException::with(['reason'])
            ->whereIn('employee_id', $memberIds)
            ->where(fn ($q) => $q
                ->whereBetween('start_at', [$this->weekStart->startOfDay(), $this->weekEnd->endOfDay()])
                ->orWhereBetween('end_at', [$this->weekStart->startOfDay(), $this->weekEnd->endOfDay()])
                ->orWhere(fn ($sub) => $sub->where('start_at', '<=', $this->weekStart)->where('end_at', '>=', $this->weekEnd))
            )
            ->get()
            ->groupBy('employee_id');

        $pendingRequests = LeaveRequest::whereIn('employee_id', $memberIds)
            ->where('status', 'pending')
            ->where(fn ($q) => $q
                ->whereBetween('start_time', [$this->weekStart->startOfDay(), $this->weekEnd->endOfDay()])
                ->orWhereBetween('end_time', [$this->weekStart->startOfDay(), $this->weekEnd->endOfDay()])
            )
            ->get()
            ->groupBy('employee_id');

        $recentSwaps = ShiftSwapRequest::with(['requester', 'recipient'])
            ->where(fn ($q) => $q->whereIn('requester_id', $memberIds)->orWhereIn('recipient_id', $memberIds))
            ->latest('start_date')
            ->take(5)
            ->get();

        $upcomingExceptions = LeaveRequest::with(['employee'])
            ->whereIn('employee_id', $memberIds)
            ->where('start_time', '>=', Carbon::now()->startOfDay())
            ->orderBy('start_time')
            ->take(5)
            ->get();

        return view('wfm::livewire.my-team', [
            'members' => $members,
            'assignments' => $assignments,
            'exceptions' => $exceptions,
            'pendingRequests' => $pendingRequests,
            'days' => $this->getWeekDays(),
            'weeklySchedule' => $weeklySchedule,
            'teams' => $availableTeams,
            'reasons' => AbsenceReasonCode::all(),
        ]);
    }

    protected function getWeekDays(): array
    {
        $days = [];
        $current = $this->weekStart->copy();
        for ($i = 0; $i < 7; $i++) {
            $days[] = $current->copy();
            $current = $current->addDay();
        }

        return $days;
    }
}
