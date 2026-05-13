<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WorkflowsModule\Models\LeaveRequest;
use App\Modules\WorkflowsModule\Models\ShiftSwapRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyTeam extends Component {
    use WithPagination;

    public $date;

    public $weekStart;

    public $weekEnd;

    public $selectedTeam = null;

    public $isManager = false;
    public $recentSwaps = [];
    public $upcomingExceptions = [];

    // Propiedades para el modal de incidentes
    public bool $showIncidentModal = false;
    public $incidentForm = [
        'id' => null,
        'employee_id' => null,
        'date' => null,
        'reason_id' => null,
        'start_time' => null,
        'end_time' => null,
        'is_full_day' => true,
        'remarks' => '',
    ];

    public function openIncidentModal($employeeId, $date) {
        $this->incidentForm['id'] = null;
        $this->incidentForm['employee_id'] = $employeeId;
        $this->incidentForm['date'] = $date;
        $this->incidentForm['is_full_day'] = true;
        $this->incidentForm['remarks'] = '';
        $this->incidentForm['reason_id'] = null;
        $this->incidentForm['start_time'] = '08:00';
        $this->incidentForm['end_time'] = '17:00';

        $this->showIncidentModal = true;
        \Flux::modal('incident-modal')->show();
    }

    public function editIncident($id) {
        $exception = ScheduleException::findOrFail($id);

        $this->incidentForm = [
            'id' => $exception->id,
            'employee_id' => $exception->employee_id,
            'date' => $exception->start_at->format('Y-m-d'),
            'reason_id' => $exception->absence_reason_code_id,
            'start_time' => $exception->start_at->format('H:i'),
            'end_time' => $exception->end_at->format('H:i'),
            'is_full_day' => (bool) $exception->is_full_day,
            'remarks' => $exception->remarks ?? '',
        ];

        $this->showIncidentModal = true;
        \Flux::modal('incident-modal')->show();
    }

    public function saveIncident() {
        $this->validate([
            'incidentForm.employee_id' => 'required',
            'incidentForm.date' => 'required|date',
            'incidentForm.reason_id' => 'required',
            'incidentForm.remarks' => 'nullable|string',
        ]);

        $startAt = Carbon::parse($this->incidentForm['date']);
        $endAt = Carbon::parse($this->incidentForm['date']);

        if ($this->incidentForm['is_full_day']) {
            $startAt = $startAt->startOfDay();
            $endAt = $endAt->endOfDay();
        } else {
            $startAt = Carbon::parse($this->incidentForm['date'] . ' ' . $this->incidentForm['start_time']);
            $endAt = Carbon::parse($this->incidentForm['date'] . ' ' . $this->incidentForm['end_time']);
        }

        $data = [
            'employee_id' => $this->incidentForm['employee_id'],
            'absence_reason_code_id' => $this->incidentForm['reason_id'],
            'start_at' => $startAt,
            'end_at' => $endAt,
            'is_full_day' => $this->incidentForm['is_full_day'],
            'remarks' => $this->incidentForm['remarks'],
            'created_by' => Auth::id(),
        ];

        if ($this->incidentForm['id']) {
            $exception = ScheduleException::findOrFail($this->incidentForm['id']);
            $exception->update($data);
            $message = __('Incidente actualizado correctamente.');
        } else {
            ScheduleException::create($data);
            $message = __('Incidente registrado correctamente.');
        }

        $this->showIncidentModal = false;
        \Flux::modal('incident-modal')->close();

        \Flux::toast($message);
    }

    public function deleteIncident($id = null) {
        $id = $id ?: $this->incidentForm['id'];
        
        if ($id) {
            $exception = ScheduleException::findOrFail($id);
            $exception->delete();

            $this->showIncidentModal = false;
            \Flux::modal('incident-modal')->close();
            
            \Flux::toast(__('Incidente eliminado correctamente.'), variant: 'warning');
        }
    }

    public function mount() {
        $this->date = Carbon::now()->format('Y-m-d');
        $this->weekStart = Carbon::now()->startOfWeek();
        $this->weekEnd = Carbon::now()->endOfWeek();

        $employee = Auth::user()->employee;
        if (!$employee) {
            abort(403, 'No tienes un perfil de empleado asociado.');
        }

        $this->isManager = $employee->is_manager;
    }

    public function updatedDate() {
        $this->weekStart = Carbon::parse($this->date)->startOfWeek();
        $this->weekEnd = Carbon::parse($this->date)->endOfWeek();
    }

    public function render() {
        $employee = Auth::user()->employee;
        $user = Auth::user();
        $isPowerUser = $user->hasAnyRole(['admin', 'wfm', 'superuser']);

        // Obtener equipos disponibles para este usuario
        if ($isPowerUser) {
            $availableTeams = Team::with('supervisor')->active()->get();
        } else {
            $directTeams = Team::with('supervisor')->where('supervisor_id', $employee->id)->get();
            $subordinateIds = $employee->getAllSubordinateIds();
            $indirectTeams = Team::with('supervisor')->whereIn('supervisor_id', $subordinateIds)->get();
            $availableTeams = $directTeams->concat($indirectTeams)->unique('id');
        }

        $subordinateIds = $isPowerUser ? [] : $employee->getAllSubordinateIds();

        // Si hay un equipo seleccionado, filtramos por los miembros de ese equipo
        $query = Employee::query()->active();

        if ($this->selectedTeam) {
            $team = Team::find($this->selectedTeam);
            $teamMemberIds = $team->users()->pluck('employees.id')->toArray();

            // Administradores y WFM ven todo el equipo.
            // Supervisores ven su equipo o sus subordinados dentro de ese equipo.
            if ($isPowerUser || $team->supervisor_id === $employee->id || $this->isManager) {
                $query->whereIn('id', $teamMemberIds);
            } else {
                $query->whereIn('id', array_intersect($teamMemberIds, $subordinateIds));
            }
        } else {
            // Si no hay equipo, Admin/WFM ve todo (o podrías limitar a un grupo grande)
            // Por ahora, si es Admin/WFM y no hay equipo, mostramos todos los activos
            if (!$isPowerUser) {
                $query->whereIn('id', $subordinateIds);
            }
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

        // Cargar solicitudes de permiso pendientes (no aprobadas aún) para la grilla
        $pendingRequests = LeaveRequest::whereIn('employee_id', $memberIds)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereBetween('start_time', [$this->weekStart->startOfDay(), $this->weekEnd->endOfDay()])
                    ->orWhereBetween('end_time', [$this->weekStart->startOfDay(), $this->weekEnd->endOfDay()]);
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
            'pendingRequests' => $pendingRequests,
            'days' => $this->getWeekDays(),
            'weeklySchedule' => $weeklySchedule,
            'teams' => $availableTeams,
            'reasons' => AbsenceReasonCode::all(),
        ]);
    }

    protected function getWeekDays() {
        $days = [];
        $current = $this->weekStart->copy();
        for ($i = 0; $i < 7; $i++) {
            $days[] = $current->copy();
            $current->addDay();
        }

        return $days;
    }
}
