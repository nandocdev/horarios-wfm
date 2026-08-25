<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Actions\DeleteScheduleExceptionAction;
use App\Modules\WfmModule\Actions\SaveScheduleExceptionAction;
use App\Modules\WfmModule\Exports\TeamIncidentsExport;
use App\Modules\WfmModule\Exports\TeamScheduleExport;
use App\Modules\WfmModule\Livewire\Forms\IncidentForm;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyTeam extends Component
{
    use WithPagination;

    public string $date = '';

    public string $weekStart = '';

    public string $weekEnd = '';

    public $selectedTeam = null;

    public bool $showIncidentModal = false;

    public IncidentForm $incidentForm;

    public function mount(): void
    {
        $this->date = Carbon::now()->format('Y-m-d');
        $this->weekStart = Carbon::now()->startOfWeek()->toDateString();
        $this->weekEnd = Carbon::now()->endOfWeek()->toDateString();

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
        $this->weekStart = Carbon::parse($this->date)->startOfWeek()->toDateString();
        $this->weekEnd = Carbon::parse($this->date)->endOfWeek()->toDateString();
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

    public function deleteIncident(DeleteScheduleExceptionAction $action, ?int $id = null): void
    {
        $id = $id ?: $this->incidentForm->id;

        if ($id) {
            $action->execute($id);
            $this->showIncidentModal = false;
            $this->dispatch('modal-close', name: 'incident-modal');
            \Flux::toast(__('Incidente eliminado correctamente.'), variant: 'warning');
        }
    }

    public function exportSchedule(TeamScheduleExport $export): Response
    {
        $weekStart = Carbon::parse($this->weekStart);
        $weekEnd = Carbon::parse($this->weekEnd);
        $members = Employee::query()->active()
            ->when($this->selectedTeam, fn ($q) => $q->where('team_id', $this->selectedTeam))
            ->orderBy('first_name')
            ->get();

        $weeklySchedule = WeeklySchedule::where('week_start_date', $this->weekStart)->first();

        $assignments = collect();
        if ($weeklySchedule) {
            $assignments = WeeklyScheduleAssignment::with(['schedule'])
                ->whereIn('employee_id', $members->pluck('id'))
                ->where('weekly_schedule_id', $weeklySchedule->id)
                ->get()
                ->groupBy('employee_id');
        }

        return $export->toXls($members, $assignments, $weekStart, $weekEnd);
    }

    public function exportIncidents(TeamIncidentsExport $export): Response
    {
        $weekStart = Carbon::parse($this->weekStart);
        $weekEnd = Carbon::parse($this->weekEnd);
        $memberIds = Employee::query()->active()
            ->when($this->selectedTeam, fn ($q) => $q->where('team_id', $this->selectedTeam))
            ->pluck('id')
            ->toArray();

        $exceptions = ScheduleException::with(['employee', 'reason'])
            ->whereIn('employee_id', $memberIds)
            ->where(fn ($q) => $q
                ->whereBetween('start_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()])
                ->orWhereBetween('end_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()])
            )
            ->orderBy('start_at')
            ->get()
            ->groupBy('employee_id');

        $teamName = $this->selectedTeam ? (Team::find($this->selectedTeam)?->name ?? 'Mi Equipo') : 'Mi Equipo';
        $periodLabel = $weekStart->format('d/m').' – '.$weekEnd->format('d/m/Y');

        return $export->toXls($exceptions, $teamName, $periodLabel);
    }

    public function render()
    {
        $weekStart = Carbon::parse($this->weekStart);
        $weekEnd = Carbon::parse($this->weekEnd);
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

        $weeklySchedule = WeeklySchedule::where('week_start_date', $this->weekStart)->first();

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
                ->whereBetween('start_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()])
                ->orWhereBetween('end_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()])
                ->orWhere(fn ($sub) => $sub->where('start_at', '<=', $weekStart)->where('end_at', '>=', $weekEnd))
            )
            ->get()
            ->groupBy('employee_id');

        $pendingRequests = LeaveRequest::whereIn('employee_id', $memberIds)
            ->where('status', 'pending')
            ->where(fn ($q) => $q
                ->whereBetween('start_time', [$weekStart->startOfDay(), $weekEnd->endOfDay()])
                ->orWhereBetween('end_time', [$weekStart->startOfDay(), $weekEnd->endOfDay()])
            )
            ->get()
            ->groupBy('employee_id');

        // requester_id/recipient_id almacenan users.id; memberIds son employees.id.
        $memberUserIds = Employee::whereIn('id', $memberIds)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->all();

        $recentSwaps = ShiftSwapRequest::with(['requester.employee', 'recipient.employee'])
            ->where(fn ($q) => $q->whereIn('requester_id', $memberUserIds)->orWhereIn('recipient_id', $memberUserIds))
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
            'recentSwaps' => $recentSwaps,
            'upcomingExceptions' => $upcomingExceptions,
        ]);
    }

    protected function getWeekDays(): array
    {
        $days = [];
        $current = Carbon::parse($this->weekStart);
        for ($i = 0; $i < 7; $i++) {
            $days[] = $current->copy();
            $current = $current->addDay();
        }

        return $days;
    }
}
