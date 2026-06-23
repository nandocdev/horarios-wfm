<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Position;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Actions\Realtime\GetExpectedAgentStateAction;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Shared\Contracts\Telemetry\TelemetryServiceInterface;
use App\Shared\Support\Metrics\MetricFormulas;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class RealtimeMonitoring extends Component
{
    use WithPagination;

    public array $stats = [];

    public ?int $teamId = null;

    public ?int $positionFilter = null;

    public ?string $queueFilter = null;

    public ?string $statusFilter = null;

    public ?string $reasonFilter = null;

    public ?string $expectedStateFilter = null;

    public bool $onlyActive = true;

    public string $search = '';

    public string $sortField = 'first_name';

    public string $sortDirection = 'asc';

    public ?int $currentWeekId = null;

    public ?object $selectedAgent = null;

    public $agentEvents = [];

    public array $operationalSettings = [];

    public array $queueAhtGoals = [];

    public function mount()
    {
        $this->authorize('monitorRealtime', Schedule::class);

        $user = auth()->user();
        $employee = $user->employee;
        $isPowerUser = $user->hasAnyRole(['admin', 'wfm', 'superuser']);

        // Validar acceso al teamId inicial
        if ($this->teamId && ! $isPowerUser) {
            $managedTeamIds = $employee?->getManagedTeamIds() ?? [];
            if (! in_array($this->teamId, $managedTeamIds)) {
                $this->teamId = null;
            }
        }

        $currentWeek = WeeklySchedule::where('week_start_date', '<=', now()->toDateString())
            ->where('week_end_date', '>=', now()->toDateString())
            ->first();

        $this->currentWeekId = $currentWeek?->id;
        $this->loadOperationalSettings();
    }

    private function loadOperationalSettings()
    {
        $this->operationalSettings = DB::table('operational_settings')
            ->pluck('value', 'key')
            ->toArray();

        $this->queueAhtGoals = CallQueue::pluck('aht_goal', 'name')->toArray();
    }

    public function clearFilters()
    {
        $this->reset(['teamId', 'positionFilter', 'queueFilter', 'statusFilter', 'reasonFilter', 'expectedStateFilter', 'search', 'onlyActive']);
    }

    public function updatedTeamId()
    {
        $this->resetPage();
    }

    public function updatedOnlyActive()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPositionFilter()
    {
        $this->resetPage();
    }

    public function updatedQueueFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedReasonFilter()
    {
        $this->resetPage();
    }

    public function updatedExpectedStateFilter()
    {
        $this->resetPage();
    }

    public function showDetails(int $empId)
    {
        $telemetryService = app(TelemetryServiceInterface::class);
        $expectedStateAction = app(GetExpectedAgentStateAction::class);

        $employee = Employee::with(['team', 'position'])->find($empId);

        if ($employee) {
            $real = $telemetryService->getBatchCurrentStates([$empId])[$empId] ?? null;
            $expected = $expectedStateAction->execute($empId);

            $this->selectedAgent = (object) [
                'id' => $employee->id,
                'employee_id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'team_name' => $employee->team?->name,
                'position_name' => $employee->position?->name,
                'current_state' => $real?->current_state ?? 'OFFLINE',
                'last_changed_at' => $real?->last_changed_at,
                'reason_code' => $real?->reason_code,
                'current_queue' => ($real->metadata['call_info']['queue_name'] ?? ($real->metadata['queue_name'] ?? ($real->metadata['csq_name'] ?? null))),
                'expected_state' => $expected,
            ];

            $this->agentEvents = AgentStateTransition::where('employee_id', $empId)
                ->whereDate('transition_time', now()->toDateString())
                ->orderBy('transition_time', 'desc')
                ->limit(10)
                ->get();

            $this->dispatch('modal-show', name: 'agent-details-modal');
        }
    }

    public function sortBy(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    #[On('refresh-realtime')]
    public function loadData()
    {
        $telemetryService = app(TelemetryServiceInterface::class);
        $expectedStateAction = app(GetExpectedAgentStateAction::class);

        // Universo operativo: Cargos IDs 1, 2, 5, 11, 13
        $employee = auth()->user()->employee;
        $isPowerUser = auth()->user()->hasAnyRole(['admin', 'wfm', 'superuser', 'chief']);
        $managedTeamIds = $employee?->getManagedTeamIds() ?? [];

        $query = Employee::query()
            ->whereIn('position_id', [1, 2, 5, 11, 13])
            ->with(['team', 'position']);

        if ($this->onlyActive) {
            $query->where('is_active', true);
        }

        // Restricción de visibilidad por rol
        if (! $isPowerUser) {
            $query->whereIn('team_id', $managedTeamIds);
        }

        if ($this->teamId) {
            $query->where('team_id', $this->teamId);
        }

        if ($this->positionFilter) {
            $query->where('position_id', $this->positionFilter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('first_name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('last_name', 'ilike', '%'.$this->search.'%');
            });
        }

        $employees = $query->get();
        $employeeIds = $employees->pluck('id')->toArray();

        // 1. Obtener estados reales via TelemetryService
        $realtimeStates = $telemetryService->getBatchCurrentStates($employeeIds);

        // 2. Obtener estados esperados via Action (Desacoplada)
        $expectedStates = $expectedStateAction->executeBatch($employeeIds);

        // 3. Cruzar datos y calcular adherencia
        $agents = $employees->map(function ($employee) use ($realtimeStates, $expectedStates) {
            $real = $realtimeStates[$employee->id] ?? null;
            $expected = $expectedStates[$employee->id] ?? [
                'type' => 'OFF',
                'label' => 'Fuera de Jornada',
                'color' => '#6b7280',
            ];

            $currentState = strtoupper($real?->current_state ?? 'OFFLINE');
            $isAdherent = $this->calculateAdherence($currentState, $expected['type'] ?? null);
            $isLogoutOrOffline = in_array($currentState, ['OFFLINE', 'LOGOUT', 'LOGGED_OUT', 'UNKNOWN']);

            // Un cambio de estado es de "hoy" si ocurrió después de las 00:00 del servidor
            // O mejor aún, comparando la fecha de la marca de tiempo con la fecha actual
            $lastChangeToday = $real?->last_changed_at
                ? Carbon::parse($real->last_changed_at)->isToday()
                : false;

            // Un usuario es ausente o desconectado si se espera que esté en SHIFT o INTRADAY
            // pero su estado es Logout/Offline.
            $isExpectedActive = in_array($expected['type'], ['SHIFT', 'INTRADAY']);

            $isAbsent = ($isExpectedActive && $isLogoutOrOffline && ! $lastChangeToday);
            $isDisconnected = ($isExpectedActive && $isLogoutOrOffline && $lastChangeToday);

            // Calcular duración de forma segura (Unix timestamp)
            $lastChangeTs = $real?->last_changed_at ? Carbon::parse($real->last_changed_at)->timestamp : 0;
            $currentDuration = $lastChangeTs > 0 ? (int) max(0, now()->timestamp - $lastChangeTs) : 0;

            return (object) [
                'id' => $employee->id,
                'emp_id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'team_name' => $employee->team?->name,
                'position_name' => $employee->position?->name,
                'current_state' => $currentState,
                'last_changed_at' => $real?->last_changed_at,
                'reason_code' => $real?->reason_code,
                'current_queue' => ($real->metadata['call_info']['queue_name'] ?? ($real->metadata['queue_name'] ?? ($real->metadata['csq_name'] ?? null))),
                'expected_state' => $expected,
                'expected_type' => $expected['type'] ?? 'OFF',
                'is_adherent' => $isAdherent,
                'is_absent' => $isAbsent,
                'is_disconnected' => $isDisconnected,
                'current_duration' => $currentDuration,
                'display_name' => $currentState,
                'color_hex' => null,
                'alerts' => $this->generateAlerts($real, $expected, $isAdherent, $isAbsent, $isDisconnected),
            ];
        });

        // 4. Aplicar filtros avanzados y Ordenamiento
        $collection = $agents->filter(function ($agent) {
            if ($this->statusFilter && $agent->current_state !== $this->statusFilter) {
                return false;
            }
            if ($this->reasonFilter && $agent->reason_code !== $this->reasonFilter) {
                return false;
            }

            if ($this->expectedStateFilter) {
                if ($this->expectedStateFilter === 'ABSENT') {
                    return $agent->is_absent;
                }
                if ($agent->expected_type !== $this->expectedStateFilter) {
                    return false;
                }
            }

            return true;
        });

        // Ordenamiento
        if ($this->sortField === 'agent') {
            $collection = $collection->sortBy(fn ($a) => $a->first_name.' '.$a->last_name, SORT_NATURAL, $this->sortDirection === 'desc');
        } elseif ($this->sortField === 'duration') {
            $collection = $collection->sortBy('current_duration', SORT_NUMERIC, $this->sortDirection === 'desc');
        } elseif ($this->sortField === 'state') {
            $collection = $collection->sortBy('current_state', SORT_NATURAL, $this->sortDirection === 'desc');
        } else {
            $collection = $collection->sortBy($this->sortField, SORT_NATURAL, $this->sortDirection === 'desc');
        }

        $this->calculateStatsFromCollection($collection);

        // Paginación Manual
        $perPage = 15;
        $currentPage = Paginator::resolveCurrentPage();
        $items = $collection->forPage($currentPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    private function calculateAdherence(?string $real, ?string $expectedType): bool
    {
        return MetricFormulas::checkAdherence($real, $expectedType);
    }

    private function generateAlerts($real, $expected, bool $isAdherent, bool $isAbsent = false, bool $isDisconnected = false): array
    {
        $alerts = [];
        $currentState = strtoupper($real?->current_state ?? 'OFFLINE');
        $isLogoutOrOffline = in_array($currentState, ['OFFLINE', 'LOGOUT', 'LOGGED_OUT', 'UNKNOWN']);
        $expectedType = $expected['type'] ?? 'OFF';

        // Si el usuario tiene una excepción programada ACTIVA ahora mismo, no se muestran alertas operativas
        if ($expectedType === 'EXCEPTION') {
            return [];
        }

        // Cargar umbrales desde settings o usar defaults
        $thresholds = [
            'personal_time' => (int) ($this->operationalSettings['personal_time_threshold'] ?? 900), // Default 15m
            'stuck_reserved' => (int) ($this->operationalSettings['stuck_reserved_threshold'] ?? 15), // Default 15s
            'long_talking' => (int) ($this->operationalSettings['long_talking_threshold'] ?? 1200), // Default 20m
            'overtime' => (int) ($this->operationalSettings['overtime_threshold'] ?? 1800), // Default 30m
            'adherence_grace' => (int) ($this->operationalSettings['adherence_alert_threshold'] ?? 0),
        ];

        // Calcular duración real en segundos
        $lastChangeTs = $real?->last_changed_at ? Carbon::parse($real->last_changed_at)->timestamp : 0;
        $durationSeconds = $lastChangeTs > 0 ? (int) max(0, now()->timestamp - $lastChangeTs) : 0;

        // 1. Alerta de Adherencia General (Solo para conectados)
        if (! $isAdherent && ! $isLogoutOrOffline && $durationSeconds >= $thresholds['adherence_grace']) {
            $label = 'Fuera de Adherencia';
            if ($expectedType === 'OFF') {
                $label = 'Tiempo Extra No Prog.';
            } elseif ($expectedType === 'SHIFT' && $currentState === 'NOT_READY') {
                $label = 'Auxiliar No Prog.';
            } elseif ($expectedType === 'INTRADAY' && $currentState === 'READY') {
                $label = 'Trabajando en Pausa';
            }

            $alerts[] = [
                'type' => 'ADHERENCE',
                'label' => $label,
                'level' => 'critical',
            ];
        }

        // 2. Ausentismo y Desconexión
        if ($isAbsent) {
            $alerts[] = [
                'type' => 'ABSENT',
                'label' => 'Ausente en Turno',
                'level' => 'critical',
            ];
        }

        if ($isDisconnected) {
            $alerts[] = [
                'type' => 'DISCONNECTED',
                'label' => 'Agente Desconectado',
                'level' => 'critical',
            ];
        }

        // 3. Alertas de Estado Específicas

        // STUCK_RESERVED
        if ($currentState === 'RESERVED' && $durationSeconds > $thresholds['stuck_reserved']) {
            $alerts[] = [
                'type' => 'STUCK_RESERVED',
                'label' => 'Reserved Pegado',
                'level' => 'critical',
            ];
        }

        // LONG_TALKING
        $currentQueue = $real->metadata['queue_name'] ?? ($real->metadata['csq_name'] ?? null);
        $queueGoal = $currentQueue ? ($this->queueAhtGoals[$currentQueue] ?? null) : null;

        $talkingThreshold = $queueGoal && $queueGoal > 0 ? $queueGoal : $thresholds['long_talking'];

        if ($currentState === 'TALKING' && $durationSeconds > $talkingThreshold) {
            $alerts[] = [
                'type' => 'LONG_TALKING',
                'label' => 'Llamada Prolongada',
                'level' => 'warning',
            ];
        }

        // PERSONAL_TIME (Not Ready Prolongado)
        if ($currentState === 'NOT_READY' && $expectedType === 'SHIFT' && $durationSeconds > $thresholds['personal_time']) {
            $alerts[] = [
                'type' => 'PERSONAL_TIME',
                'label' => 'Not Ready Prolongado',
                'level' => 'warning',
            ];
        }

        // OVERTIME
        if ($expectedType === 'OFF' && in_array($currentState, ['READY', 'TALKING', 'WORK']) && $durationSeconds > $thresholds['overtime']) {
            $alerts[] = [
                'type' => 'OVERTIME',
                'label' => 'Exceso de Extra',
                'level' => 'warning',
            ];
        }

        return $alerts;
    }

    private function calculateStatsFromCollection($collection)
    {
        $connectedAgents = $collection->filter(function ($agent) {
            return ! in_array(strtoupper($agent->current_state), ['OFFLINE', 'LOGOUT', 'LOGGED_OUT', 'UNKNOWN']);
        });

        $this->stats = [
            'total' => $collection->count(),
            'adherent' => $collection->where('is_adherent', true)->count(),
            'non_adherent' => $collection->where('is_adherent', false)->count(),
            'ready' => $collection->where('current_state', 'READY')->count(),
            'talking' => $collection->where('current_state', 'TALKING')->count(),
            'not_ready' => $collection->where('current_state', 'NOT_READY')->count(),
            'absent_count' => $collection->where('is_absent', true)->count(),
            'disconnected_count' => $collection->where('is_disconnected', true)->count(),
            // Adherencia basada SOLO en el universo de conectados
            'adherence_percent' => $connectedAgents->count() > 0
                ? (int) round(($connectedAgents->where('is_adherent', true)->count() / $connectedAgents->count()) * 100)
                : 0,
            'worker_active' => $this->stats['worker_active'] ?? false,
            'worker_last_update' => $this->stats['worker_last_update'] ?? 'Nunca',
        ];
    }

    public function render()
    {
        // Calcular estado del worker una sola vez por render
        $lastUpdate = AgentRealtimeState::max('updated_at');
        $this->stats['worker_active'] = $lastUpdate ? now()->parse($lastUpdate)->diffInMinutes() < 2 : false;
        $this->stats['worker_last_update'] = $lastUpdate ? now()->parse($lastUpdate)->diffForHumans() : 'Nunca';

        $employee = auth()->user()->employee;
        $isPowerUser = auth()->user()->hasAnyRole(['admin', 'wfm', 'superuser', 'chief']);

        return view('operations::livewire.realtime-monitoring', [
            'agents' => $this->loadData(),
            'teams' => $isPowerUser
                ? Team::all()
                : Team::whereIn('id', $employee?->getManagedTeamIds() ?? [])->get(),
            'positions' => Position::all(),
            'queues' => CallQueue::all(),
            'reasons' => AgentRealtimeState::whereNotNull('reason_code')
                ->distinct()
                ->pluck('reason_code')
                ->sort(),
            'expectedStateOptions' => [
                'SHIFT' => 'En Turno',
                'INTRADAY' => 'Actividad Programada',
                'EXCEPTION' => 'Excepción/Permiso',
                'ABSENT' => 'Ausentes',
                'OFF' => 'Fuera de Jornada',
            ],
        ]);
    }
}
