<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\OperationsModule\Actions\GetStandardizedPerformanceAction;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Url;

class PerformanceScorecard extends Component
{
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

    public function mount()
    {
        $this->date = $this->date ?: now()->toDateString();
        $this->authorize('viewPerformance', Employee::class);
        $this->loadPerformance();
    }

    public function updatedEmployeeId(): void
    {
        $this->loadPerformance();
    }

    public function updatedTeamId(): void
    {
        $this->loadPerformance();
    }

    public function updatedPeriodType(): void
    {
        $this->loadPerformance();
    }

    public function updatedDate(): void
    {
        $this->loadPerformance();
    }

    public function loadPerformance()
    {
        $action = app(GetStandardizedPerformanceAction::class);
        $carbonDate = Carbon::parse($this->date);
        
        $data = [];

        if ($this->employeeId) {
            $employee = Employee::find($this->employeeId);
            if (!$employee) return;

            $dates = match($this->periodType) {
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

            if ($this->teamId) {
                $query->where('team_id', $this->teamId);
            }

            if ($this->search) {
                $query->where('name', 'ilike', '%' . $this->search . '%');
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

    public function formatMinutes(float $minutes): string
    {
        $seconds = (int) round($minutes * 60);
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    public function render()
    {
        $employees = Employee::query()
            ->whereIn('position_id', [1, 2, 5])
            ->when($this->teamId, fn($q) => $q->where('team_id', $this->teamId))
            ->orderBy('first_name')
            ->get();

        return view('operations::livewire.performance-scorecard', [
            'teams' => Team::all(),
            'employees' => $employees,
        ]);
    }
}
