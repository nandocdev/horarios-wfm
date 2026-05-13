<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\OperationsModule\Actions\GetEmployeePerformanceAction;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Shared\Support\Metrics\MetricFormulas;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class TeamPerformanceSummary extends Component
{
    #[Url]
    public ?int $teamId = null;

    #[Url]
    public string $selectedDate = '';

    public array $teamPerformance = [];
    public array $teamTotals = [
        'utilization' => 0,
        'productivity' => 0,
        'total_productive' => 0,
        'total_connected' => 0,
        'total_calls' => 0,
        'absenteeism' => 0,
        'absent_count' => 0,
        'exception_count' => 0,
    ];

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
        
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) return;

        $managedIds = $user->hasRole(['admin', 'wfm', 'director']) 
            ? null 
            : $employee->getManagedTeamIds();

        // Validar acceso al teamId si viene por URL
        if ($this->teamId && $managedIds !== null && !in_array($this->teamId, $managedIds)) {
            $this->teamId = null;
        }

        if ($this->teamId === null && $employee->team_id) {
            $this->teamId = $employee->team_id;
        }

        $this->loadTeamPerformance(app(GetEmployeePerformanceAction::class));
    }

    public function updatedTeamId(): void
    {
        $this->loadTeamPerformance(app(GetEmployeePerformanceAction::class));
    }

    public function updatedSelectedDate(): void
    {
        $this->loadTeamPerformance(app(GetEmployeePerformanceAction::class));
    }

    #[Computed]
    public function teams(): Collection
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) return collect();

        if ($user->hasRole(['admin', 'wfm', 'director'])) {
            return Team::active()->orderBy('name')->get();
        }

        // Obtener equipos gestionados directa o indirectamente
        $managedIds = $employee->getManagedTeamIds();
        
        return Team::whereIn('id', $managedIds)
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function loadTeamPerformance(GetEmployeePerformanceAction $action): void
    {
        if (!$this->teamId) {
            $this->teamPerformance = [];
            $this->resetTotals();
            return;
        }

        $employees = Employee::where('team_id', $this->teamId)
            ->active()
            ->orderBy('first_name')
            ->get();

        $date = Carbon::parse($this->selectedDate);
        $data = [];
        
        $totalUtil = 0;
        $totalProd = 0;
        $tp = 0;
        $tc = 0;
        $calls = 0;
        $presentCount = 0;
        $absentCount = 0;
        $exceptionCount = 0;
        $totalEmployees = $employees->count();

        foreach ($employees as $employee) {
            $performance = $action->execute($employee, $date);
            $perfArray = $performance->toArray();
            
            $data[] = [
                'employee' => [
                    'id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'avatar' => $employee->avatar_url,
                ],
                'performance' => $perfArray,
            ];

            $metrics = $perfArray['metrics'];
            $attendance = $perfArray['attendance'];
            
            if ($attendance['status'] === 'ausente') {
                $absentCount++;
            } elseif ($attendance['status'] === 'excepción') {
                $exceptionCount++;
            } else {
                $totalUtil += $metrics['utilization_percentage'];
                $totalProd += $metrics['productivity_percentage'];
                $tp += $metrics['total_productive_minutes'];
                $tc += $metrics['total_connected_minutes'];
                $presentCount++;
            }
            
            foreach ($perfArray['queues'] as $queue) {
                $calls += $queue['total_calls'];
            }
        }

        $this->teamPerformance = $data;
        
        $this->teamTotals = [
            'utilization' => $presentCount > 0 ? round($totalUtil / $presentCount, 1) : 0,
            'productivity' => $presentCount > 0 ? round($totalProd / $presentCount, 1) : 0,
            'total_productive' => $tp,
            'total_connected' => $tc,
            'total_calls' => $calls,
            // Ausentismo: (Ausencias Injustificadas) / (Total - Excepciones Programadas)
            'absenteeism' => ($totalEmployees - $exceptionCount) > 0 
                ? round(($absentCount / ($totalEmployees - $exceptionCount)) * 100, 1) 
                : 0,
            'absent_count' => $absentCount,
            'exception_count' => $exceptionCount,
            'total_employees' => $totalEmployees,
        ];
    }

    private function resetTotals(): void
    {
        $this->teamTotals = [
            'utilization' => 0,
            'productivity' => 0,
            'total_productive' => 0,
            'total_connected' => 0,
            'total_calls' => 0,
            'absenteeism' => 0,
            'absent_count' => 0,
            'exception_count' => 0,
            'total_employees' => 0,
        ];
    }

    public function formatMinutes(float $minutes): string
    {
        return MetricFormulas::formatDuration((int) round($minutes * 60));
    }

    public function render()
    {
        return view('operations::livewire.team-performance-summary', [
            'teams' => $this->teams,
        ])->layout('layouts.app');
    }
}
