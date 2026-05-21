<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\OperationsModule\Actions\GetStandardizedPerformanceAction;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

class PerformanceScorecard extends Component {
    #[Url]
    public string $date = '';

    #[Url]
    public ?int $employeeId = null;

    #[Url]
    public ?int $teamId = null;

    #[Url]
    public string $periodType = 'daily';

    public string $search = '';

    public array $performanceData = [];

    public function mount() {
        $this->date = $this->date ?: now()->toDateString();
        $this->authorize('viewPerformance', Employee::class);

        $employee = auth()->user()->employee;
        if (!$this->employeeId && $employee) {
            $this->employeeId = $employee->id;
        }

        $this->loadPerformance();
    }

    public function updatedEmployeeId(): void {
        $this->loadPerformance();
    }

    public function updatedTeamId(): void {
        $this->employeeId = null; // Reset employee when changing team
        $this->loadPerformance();
    }

    public function updatedPeriodType(): void {
        $this->loadPerformance();
    }

    public function updatedSearch(): void {
        $this->loadPerformance();
    }

    public function updatedDate(): void {
        $this->loadPerformance();
    }

    public function loadPerformance() {
        $action = app(GetStandardizedPerformanceAction::class);
        $carbonDate = Carbon::parse($this->date);
        $user = auth()->user();
        $me = $user->employee;
        $isPowerUser = $user->hasAnyRole(['admin', 'wfm', 'director', 'chief']);

        $data = [];

        if ($this->employeeId) {
            $employee = Employee::find($this->employeeId);

            // Validar acceso al empleado solicitado
            if (!$employee || (!$isPowerUser && !$user->can('viewPerformance', $employee))) {
                $this->employeeId = $me?->id;
                $employee = $me;
            }

            if (!$employee) {
                return;
            }

            $dates = match ($this->periodType) {
                'weekly' => collect(range(0, 6))->map(fn($i) => $carbonDate->copy()->startOfWeek()->addDays($i)),
                'monthly' => collect(range(0, $carbonDate->daysInMonth - 1))->map(fn($i) => $carbonDate->copy()->startOfMonth()->addDays($i)),
                default => [$carbonDate]
            };

            foreach ($dates as $date) {
                $performance = $action->execute($employee, $date)->toArray();
                $performance['employee'] = [
                    'full_name' => $employee->full_name,
                    'avatar' => $employee->avatar_url,
                ];
                $data[] = $performance;
            }
        } else {
            $query = Employee::query()
                ->whereIn('position_id', [1, 2, 5])
                ->with(['team', 'position']);

            if (!$isPowerUser) {
                $managedTeamIds = $me?->getManagedTeamIds() ?? [];
                $query->whereIn('team_id', $managedTeamIds);
            }

            if ($this->teamId) {
                $query->where('team_id', $this->teamId);
            }

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('first_name', 'ilike', '%' . $this->search . '%')
                        ->orWhere('last_name', 'ilike', '%' . $this->search . '%');
                });
            }

            $employees = $query->get();

            foreach ($employees as $employee) {
                $performance = $action->execute($employee, $carbonDate)->toArray();
                $performance['employee'] = [
                    'full_name' => $employee->full_name,
                    'avatar' => $employee->avatar_url,
                ];
                $data[] = $performance;
            }
        }

        $this->performanceData = $data;
    }

    public function formatMinutes(float $minutes): string {
        $seconds = (int) round($minutes * 60);
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    public function render() {
        $user = auth()->user();
        $employee = $user->employee;
        $isPowerUser = $user->hasAnyRole(['admin', 'wfm', 'director', 'chief']);

        $managedTeamIds = $employee?->getManagedTeamIds() ?? [];

        $teams = $isPowerUser
            ? Team::all()
            : Team::whereIn('id', $managedTeamIds)->get();

        $employeesQuery = Employee::query()
            ->whereIn('position_id', [1, 2, 5])
            ->orderBy('first_name');

        if (!$isPowerUser) {
            $employeesQuery->whereIn('team_id', $managedTeamIds);
        }

        if ($this->teamId) {
            $employeesQuery->where('team_id', $this->teamId);
        }

        return view('operations::livewire.performance-scorecard', [
            'teams' => $teams,
            'employees' => $employeesQuery->get(),
        ]);
    }
}
