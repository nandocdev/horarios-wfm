<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\OperationsModule\Actions\GetEmployeePerformanceAction;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use App\Shared\Contracts\WfmModule\ExpectedAgentStateInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    public array $roster = [];

    public string $ahtChartOptions = '{}';

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

        $this->teams = collect();
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
        $this->roster = [];

        if (! $this->teamId) {
            $this->headcount = [];
            $this->teamKpis = [];
            $this->stateDistribution = [];
            $this->alerts = [];
            $this->ahtChartOptions = '{}';

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
        $this->buildAhtChartData($employeeIds, $today);
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

        $perfRecords = AgentCallPerformance::whereIn('employee_id', $employeeIds)
            ->whereDate('start_time', $today)
            ->get();

        $inboundRecords = $perfRecords->whereNotNull('csq_name');
        $outboundRecords = $perfRecords->whereNull('csq_name');

        $totalCalls = $perfRecords->count();
        $totalInbound = $inboundRecords->count();
        $totalOutbound = $outboundRecords->count();
        $totalTalk = $perfRecords->sum('talk_time');
        $totalWork = $perfRecords->sum('work_time');
        $totalDurationInbound = $inboundRecords->sum('total_duration');
        $avgAht = $totalInbound > 0 ? round($totalDurationInbound / $totalInbound, 1) : 0;

        $perfByEmployee = $perfRecords->groupBy('employee_id');

        $roster = [];

        foreach ($employees as $employee) {
            $realState = $states->get($employee->id);
            $currentState = $realState->current_state ?? 'OFFLINE';
            $isConnected = ! in_array($currentState, ['LOGOUT', 'OFFLINE', 'UNKNOWN']);

            $perf = $action->execute($employee, $date);
            $metrics = $perf->toArray()['metrics'] ?? [];

            $empRecords = $perfByEmployee->get($employee->id, collect());
            $empInbound = $empRecords->whereNotNull('csq_name');
            $empOutbound = $empRecords->whereNull('csq_name');

            $empCallsInbound = $empInbound->count();
            $empCallsOutbound = $empOutbound->count();

            $empDurationInbound = $empInbound->sum('total_duration');
            $empTalkOutbound = $empOutbound->sum('talk_time');
            $empWorkOutbound = $empOutbound->sum('work_time');

            $empAhtInbound = $empCallsInbound > 0 ? round($empDurationInbound / $empCallsInbound, 1) : 0;
            $empAhtOutbound = $empCallsOutbound > 0 ? round(($empTalkOutbound + $empWorkOutbound) / $empCallsOutbound, 1) : 0;

            $expected = $expectedState->execute((int) $employee->id, $date);
            $expectedType = $expected['type'] ?? 'OFF';

            $roster[] = [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'state' => $currentState,
                'is_connected' => $isConnected,
                'reason' => $realState->reason_code ?? null,
                'aht' => $empAhtInbound,
                'calls_inbound' => $empCallsInbound,
                'calls_outbound' => $empCallsOutbound,
                'aht_inbound' => $empAhtInbound,
                'aht_outbound' => $empAhtOutbound,
                'talk_time' => $empInbound->sum('talk_time') + $empTalkOutbound,
                'occupancy' => (float) ($metrics['occupancy'] ?? 0),
                'productivity' => (float) ($metrics['productivity_percentage'] ?? 0),
                'utilization' => (float) ($metrics['utilization_percentage'] ?? 0),
                'connected_minutes' => (int) ($metrics['total_connected_minutes'] ?? 0),
                'adherence' => $expectedType,
                'adherence_color' => $expectedType === 'SHIFT' ? 'success' : ($expectedType === 'INTRADAY' ? 'warning' : 'muted'),
            ];
        }

        $this->roster = $roster;

        $this->teamKpis = array_merge($this->teamKpis, [
            'total_calls' => $totalCalls,
            'total_inbound' => $totalInbound,
            'total_outbound' => $totalOutbound,
            'avg_aht' => $avgAht,
        ]);
    }

    private function buildKpis(): void
    {
        $roster = collect($this->roster);

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

    private function buildAhtChartData(array $employeeIds, string $today): void
    {
        $raw = DB::table('call_records')
            ->join('employees', 'call_records.employee_id', '=', 'employees.id')
            ->join('call_queues', 'call_records.queue_id', '=', 'call_queues.id')
            ->whereIn('call_records.employee_id', $employeeIds)
            ->whereNotNull('call_records.queue_id')
            ->whereDate('call_records.ivr_started_at', $today)
            ->select([
                'employees.id as employee_id',
                DB::raw("TRIM(COALESCE(employees.first_name, '') || ' ' || COALESCE(employees.last_name, '')) as full_name"),
                'call_queues.id as queue_id',
                'call_queues.name as queue_name',
                DB::raw('AVG(COALESCE(call_records.talk_time, 0) + COALESCE(call_records.work_time, 0)) as avg_aht'),
            ])
            ->groupBy('employees.id', 'employees.first_name', 'employees.last_name', 'call_queues.id', 'call_queues.name')
            ->orderBy('employees.first_name')
            ->orderBy('employees.last_name')
            ->get();

        if ($raw->isEmpty()) {
            $this->ahtChartOptions = '{}';

            return;
        }

        $agentNames = $raw->pluck('full_name')->unique()->values()->toArray();
        $queueNames = $raw->pluck('queue_name')->unique()->values()->toArray();

        $series = [];
        $palette = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'];

        foreach ($queueNames as $i => $queueName) {
            $queueData = $raw->where('queue_name', $queueName);
            $data = [];
            foreach ($agentNames as $agentName) {
                $match = $queueData->firstWhere('full_name', $agentName);
                $data[] = $match ? round((float) $match->avg_aht, 1) : 0;
            }
            $series[] = [
                'name' => $queueName,
                'data' => $data,
            ];
        }

        $this->ahtChartOptions = json_encode([
            'chart' => [
                'type' => 'bar',
                'toolbar' => ['show' => false],
                'zoom' => ['enabled' => false],
                'fontFamily' => 'inherit',
                'animations' => ['enabled' => false],
                'height' => '300px',
            ],
            'series' => $series,
            'xaxis' => [
                'categories' => $agentNames,
                'labels' => [
                    'style' => ['fontSize' => '10px'],
                    'rotate' => -45,
                    'trim' => true,
                    'maxHeight' => 80,
                ],
                'title' => ['text' => 'Agente'],
            ],
            'yaxis' => [
                'title' => ['text' => 'AHT (segundos)'],
                'labels' => ['formatter' => 'function(v){return Number(v).toFixed(0)+"s"}'],
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '70%',
                ],
            ],
            'dataLabels' => ['enabled' => false],
            'stroke' => ['show' => true, 'width' => 1, 'colors' => ['#fff']],
            'tooltip' => [
                'shared' => true,
                'intersect' => false,
                'y' => ['formatter' => 'function(v){return v ? Number(v).toFixed(1)+"s" : "-"}'],
            ],
            'colors' => array_slice($palette, 0, count($queueNames)),
            'legend' => [
                'position' => 'top',
                'fontSize' => '10px',
                'itemMargin' => ['horizontal' => 8],
            ],
            'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 2],
            'noData' => ['text' => 'Sin datos de AHT para la fecha seleccionada'],
        ]);
    }

    public function render()
    {
        $this->sanitizeNumericProperties();

        return view('wfm::livewire.team-dashboard', [
            'teams' => $this->resolveTeams(),
        ])->layout('layouts.app', ['title' => 'Dashboard del Equipo']);
    }

    private function sanitizeNumericProperties(): void
    {
        foreach (['headcount', 'teamKpis', 'roster'] as $prop) {
            array_walk_recursive($this->$prop, function (&$v) {
                if (is_float($v) && (is_nan($v) || is_infinite($v))) {
                    $v = 0.0;
                }
            });
        }
    }
}
