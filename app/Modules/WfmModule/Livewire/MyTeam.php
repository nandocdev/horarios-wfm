<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WorkflowsModule\Models\LeaveRequest;
use App\Modules\WorkflowsModule\Models\ShiftSwapRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyTeam extends Component
{
    use WithPagination;

    public $date;

    public $weekStart;

    public $weekEnd;

    public $selectedTeam = null;

    public $teams = [];

    public $isManager = false;

    public $recentSwaps = [];

    public $upcomingExceptions = [];

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
        $this->weekStart = Carbon::now()->startOfWeek();
        $this->weekEnd = Carbon::now()->endOfWeek();

        $employee = Auth::user()->employee;
        if (! $employee) {
            abort(403, 'No tienes un perfil de empleado asociado.');
        }

        $this->isManager = $employee->is_manager;

        // Cargar equipos bajo su mando
        // Si es supervisor directo en la tabla Team
        $directTeams = Team::where('supervisor_id', $employee->id)->get();

        // Si es manager, podría tener equipos de sus subordinados
        $subordinateIds = $employee->getAllSubordinateIds();
        $indirectTeams = Team::whereIn('supervisor_id', $subordinateIds)->get();

        $this->teams = $directTeams->concat($indirectTeams)->unique('id');

        if ($this->teams->count() > 0) {
            $this->selectedTeam = $this->teams->first()->id;
        }
    }

    public function updatedDate()
    {
        $this->weekStart = Carbon::parse($this->date)->startOfWeek();
        $this->weekEnd = Carbon::parse($this->date)->endOfWeek();
    }

    public function render()
    {
        $employee = Auth::user()->employee;
        $subordinateIds = $employee->getAllSubordinateIds();

        // Si hay un equipo seleccionado, filtramos por los miembros de ese equipo que sean subordinados
        // O si es el supervisor directo del equipo, todos los miembros activos del equipo
        $query = Employee::query()->active();

        if ($this->selectedTeam) {
            $team = Team::find($this->selectedTeam);
            $teamMemberIds = $team->users()->pluck('employees.id')->toArray();

            // Solo puede ver si es supervisor del equipo o si los empleados son sus subordinados
            if ($team->supervisor_id === $employee->id || $this->isManager) {
                $query->whereIn('id', $teamMemberIds);
            } else {
                $query->whereIn('id', array_intersect($teamMemberIds, $subordinateIds));
            }
        } else {
            $query->whereIn('id', $subordinateIds);
        }

        $members = $query->with(['position'])->orderBy('first_name')->get();
        $memberIds = $members->pluck('id')->toArray();

        // Encontrar el contenedor de la semana
        $weeklySchedule = WeeklySchedule::where('week_start_date', $this->weekStart->format('Y-m-d'))->first();

        // Cargar asignaciones para la semana
        $assignments = collect();
        if ($weeklySchedule) {
            $assignments = WeeklyScheduleAssignment::with(['schedule'])
                ->whereIn('employee_id', $memberIds)
                ->where('weekly_schedule_id', $weeklySchedule->id)
                ->get()
                ->groupBy('employee_id');
        }

        // Cargar excepciones (permisos, etc) para la grilla
        $exceptions = ScheduleException::with(['reason'])
            ->whereIn('employee_id', $memberIds)
            ->where(function ($q) {
                $q->whereBetween('start_at', [$this->weekStart->startOfDay(), $this->weekEnd->endOfDay()])
                    ->orWhereBetween('end_at', [$this->weekStart->startOfDay(), $this->weekEnd->endOfDay()])
                    ->orWhere(function ($sub) {
                        $sub->where('start_at', '<=', $this->weekStart)
                            ->where('end_at', '>=', $this->weekEnd);
                    });
            })
            ->get()
            ->groupBy('employee_id');

        // Cargar actividad reciente para los paneles
        $this->recentSwaps = ShiftSwapRequest::with(['requester', 'recipient'])
            ->where(function ($q) use ($memberIds) {
                $q->whereIn('requester_id', $memberIds)
                    ->orWhereIn('recipient_id', $memberIds);
            })
            ->latest('requested_date')
            ->take(5)
            ->get();

        $this->upcomingExceptions = LeaveRequest::with(['employee'])
            ->whereIn('employee_id', $memberIds)
            ->where('start_time', '>=', Carbon::now()->startOfDay())
            ->orderBy('start_time')
            ->take(5)
            ->get();

        return view('wfm::livewire.my-team', [
            'members' => $members,
            'assignments' => $assignments,
            'exceptions' => $exceptions,
            'days' => $this->getWeekDays(),
            'weeklySchedule' => $weeklySchedule,
        ]);
    }

    protected function getWeekDays()
    {
        $days = [];
        $current = $this->weekStart->copy();
        for ($i = 0; $i < 7; $i++) {
            $days[] = $current->copy();
            $current->addDay();
        }

        return $days;
    }
}
