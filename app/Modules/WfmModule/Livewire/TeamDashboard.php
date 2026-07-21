<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\OperationsModule\Actions\GetEmployeePerformanceAction;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use App\Shared\Contracts\WfmModule\ExpectedAgentStateInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class TeamDashboard extends Component
{
    #[Url]
    public ?int $teamId = null;

    #[Url]
    public string $selectedDate = '';

    public array $headcount = [];

    public array $teamKpis = [];

    public array $stateDistribution = [];

    public array $alerts = [];

    public Collection $roster;

    public Collection $teams;

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();

        $user = auth()->user();
        $employee = $user->employee;

        if ($employee) {
            $managedIds = $user->hasRole(['admin', 'wfm', 'director'])
                ? null
                : $employee->getManagedTeamIds();

            if ($this->teamId && $managedIds !== null && ! in_array($this->teamId, $managedIds)) {
                $this->teamId = null;
            }

            if ($this->teamId === null && $employee->team_id) {
                $this->teamId = $employee->team_id;
            }
        }

        $this->teams = $this->resolveTeams();
        $this->loadDashboard();
    }

    public function updatedTeamId(): void
    {
        $this->loadDashboard();
    }

    public function updatedSelectedDate(): void
    {
        $this->loadDashboard();
    }

    public function loadDashboard(): void
    {
        $this->roster = collect();

        if (! $this->teamId) {
            $this->headcount = [];
            $this->teamKpis = [];
            $this->stateDistribution = [];
            $this->alerts = [];

            return;
        }

        $today = $this->selectedDate;
        $date = Carbon::parse($today);
        $dayOfWeek = $date->dayOfWeekIso;

        $employees = Employee::where('team_id', $this->teamId)
            ->active()
            ->orderBy('first_name')
            ->get();

        $employeeIds = $employees->pluck('id')->toArray();

        $this->buildHeadcount($employees, $today, $dayOfWeek);
        $this->buildRoster($employees, $date, $employeeIds, $today);
        $this->buildKpis();
        $this->buildStateDistribution($employeeIds);
        $this->buildAlerts($employeeIds, $today, $dayOfWeek);
    }

    private function resolveTeams(): Collection
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            return collect();
        }

        if ($user->hasRole(['admin', 'wfm', 'director'])) {
            return Team::active()->orderBy('name')->get();
        }

        $managedIds = $employee->getManagedTeamIds();

        return Team::whereIn('id', $managedIds)->active()->orderBy('name')->get();
    }

    private function buildHeadcount(Collection $employees, string $today, int $dayOfWeek): void
    {
        $total = $employees->count();
        $present = 0;
        $vacation = 0;
        $leave = 0;
        $absent = 0;
        $swap = 0;

        $now = Carbon::parse($today);
        $realtimeRepo = app(TelemetryRealtimeRepositoryInterface::class);
        $scheduleQueries = app(DashboardScheduleQueriesInterface::class);

        $states = $realtimeRepo->getRealtimeStates($employees->pluck('id')->toArray());
        $connectedIds = $states->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])->pluck('employee_id');

        $exceptions = $scheduleQueries->getExceptionCount(
            $employees->pluck('id')->toArray(),
            $today
        );

        $leaveCounts = $scheduleQueries->getLeaveCounts(
            $employees->pluck('id')->toArray(),
            $today
        );

        foreach ($employees as $emp) {
            $isPresent = $connectedIds->contains($emp->id);

            if ($isPresent) {
                $present++;
            } elseif ($leaveCounts['approved'] > 0) {
                $leave++;
            } elseif ($exceptions > 0) {
                $vacation++;
            } elseif (! $isPresent) {
                $absent++;
            }
        }

        $shrinkage = $total > 0
            ? round((($total - $present) / $total) * 100, 1)
            : 0;

        $this->headcount = [
            'total' => $total,
            'present' => $present,
            'vacation' => $vacation,
            'leave' => $leave,
            'absent' => $absent,
            'swap' => $swap,
            'shrinkage' => $shrinkage,
        ];
    }

    private function buildRoster(
        Collection $employees,
        Carbon $date,
        array $employeeIds,
        string $today,
    ): void {
        $action = app(GetEmployeePerformanceAction::class);
        $realtimeRepo = app(TelemetryRealtimeRepositoryInterface::class);
        $expectedState = app(ExpectedAgentStateInterface::class);

        $states = $realtimeRepo->getRealtimeStates($employeeIds)->keyBy('employee_id');
        $stats = $realtimeRepo->getQueuePerformanceReport($today, $employeeIds);

        $totalCalls = $stats->sum('handled');
        $totalTalk = $stats->sum('total_talk');
        $avgAht = $totalCalls > 0 ? round($totalTalk / $totalCalls, 1) : 0;

        $roster = [];

        foreach ($employees as $employee) {
            $realState = $states->get($employee->id);
            $currentState = $realState->current_state ?? 'OFFLINE';
            $isConnected = ! in_array($currentState, ['LOGOUT', 'OFFLINE', 'UNKNOWN']);

            $perf = $action->execute($employee, $date);
            $metrics = $perf->toArray()['metrics'] ?? [];

            $empStats = $realtimeRepo->getQueuePerformanceReport($today, [$employee->id]);
            $empCalls = $empStats->sum('handled');
            $empTalk = $empStats->sum('total_talk');
            $empAht = $empCalls > 0 ? round($empTalk / $empCalls, 1) : 0;

            $expected = $expectedState->execute((int) $employee->id, $date);
            $expectedType = $expected['type'] ?? 'OFF';

            $roster[] = [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'state' => $currentState,
                'is_connected' => $isConnected,
                'reason' => $realState->reason_code ?? null,
                'aht' => $empAht,
                'calls' => $empCalls,
                'talk_time' => $empTalk,
                'occupancy' => (float) ($metrics['occupancy'] ?? 0),
                'productivity' => (float) ($metrics['productivity_percentage'] ?? 0),
                'utilization' => (float) ($metrics['utilization_percentage'] ?? 0),
                'connected_minutes' => (int) ($metrics['total_connected_minutes'] ?? 0),
                'adherence' => $expectedType,
                'adherence_color' => $expectedType === 'SHIFT' ? 'success' : ($expectedType === 'INTRADAY' ? 'warning' : 'muted'),
            ];
        }

        $this->roster = collect($roster);

        $this->teamKpis = array_merge($this->teamKpis, [
            'total_calls' => $totalCalls,
            'avg_aht' => $avgAht,
        ]);
    }

    private function buildKpis(): void
    {
        $roster = $this->roster;

        $connected = $roster->where('is_connected', true);
        $connectedCount = $connected->count();
        $totalCount = $roster->count();

        $avgProductivity = $connectedCount > 0
            ? round($connected->sum('productivity') / $connectedCount, 1)
            : 0;

        $avgOccupancy = $connectedCount > 0
            ? round($connected->sum('occupancy') / $connectedCount, 1)
            : 0;

        $onShift = $roster->where('adherence', 'SHIFT')->count();
        $totalTracked = $onShift;
        $sla = $totalTracked > 0
            ? round(($roster->where('is_connected', true)->count() / max($totalTracked, 1)) * 100, 1)
            : 0;

        $this->teamKpis = array_merge($this->teamKpis, [
            'avg_productivity' => $avgProductivity,
            'avg_occupancy' => $avgOccupancy,
            'sla' => $sla,
            'connected_count' => $connectedCount,
            'total_count' => $totalCount,
        ]);
    }

    private function buildStateDistribution(array $employeeIds): void
    {
        $realtimeRepo = app(TelemetryRealtimeRepositoryInterface::class);
        $states = $realtimeRepo->getRealtimeStates($employeeIds);

        $counts = $states->groupBy(fn ($s) => strtoupper($s->current_state))
            ->map->count();

        $this->stateDistribution = [
            'TALKING' => $counts->get('TALKING', 0),
            'READY' => $counts->get('READY', 0),
            'NOT_READY' => $counts->get('NOT_READY', 0),
            'WORK' => $counts->get('WORK', 0) + $counts->get('ACW', 0),
            'RESERVED' => $counts->get('RESERVED', 0),
            'OFFLINE' => $counts->get('LOGOUT', 0) + $counts->get('OFFLINE', 0) + $counts->get('UNKNOWN', 0),
            'NOT_READY_LUNCH' => $counts->get('NOT_READY_LUNCH', 0) + $counts->get('LUNCH', 0),
            'NOT_READY_BREAK' => $counts->get('NOT_READY_BREAK', 0) + $counts->get('BREAK', 0),
        ];
    }

    private function buildAlerts(array $employeeIds, string $today, int $dayOfWeek): void
    {
        $alerts = [];
        $realtimeRepo = app(TelemetryRealtimeRepositoryInterface::class);
        $states = $realtimeRepo->getRealtimeStates($employeeIds);
        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');

        foreach ($states as $state) {
            $stateName = strtoupper($state->current_state);

            $agentName = $employees->get($state->employee_id)?->full_name ?? "Agente #{$state->employee_id}";

            if ($stateName === 'NOT_READY' && $state->reason_code) {
                $alerts[] = [
                    'level' => 'warning',
                    'type' => 'not_ready',
                    'message' => "{$agentName} en NOT_READY: {$state->reason_code}",
                ];
            }

            if (in_array($stateName, ['OFFLINE', 'LOGOUT', 'UNKNOWN'])) {
                $alerts[] = [
                    'level' => 'danger',
                    'type' => 'offline',
                    'message' => "{$agentName} desconectado",
                ];
            }
        }

        $this->alerts = array_slice($alerts, 0, 10);
    }

    public function render()
    {
        return view('wfm::livewire.team-dashboard', [
            'teams' => $this->teams,
        ])->layout('layouts.app', ['title' => 'Dashboard del Equipo']);
    }
}
