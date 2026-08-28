<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\OperationsModule\Actions\GetStandardizedPerformanceAction;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Shared\Contracts\Employees\EmployeeInterface;
use App\Shared\Contracts\Employees\EmployeeRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;
use Livewire\Component;

class PerformanceScorecard extends Component
{
    private const POSITION_IDS = [1, 2, 5, 11, 13];

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

    public function mount(EmployeeRepositoryInterface $employees): void
    {
        $this->date = $this->date ?: now()->toDateString();
        $this->authorize('viewPerformance', Employee::class);

        $employee = $employees->findByUser(auth()->id());
        if (! $this->employeeId && $employee) {
            $this->employeeId = (int) $employee->getId();
        }

        $this->loadPerformance($employees);
    }

    public function updatedEmployeeId(EmployeeRepositoryInterface $employees): void
    {
        $this->loadPerformance($employees);
    }

    public function updatedTeamId(EmployeeRepositoryInterface $employees): void
    {
        $this->employeeId = null; // Reset employee when changing team
        $this->loadPerformance($employees);
    }

    public function updatedPeriodType(EmployeeRepositoryInterface $employees): void
    {
        $this->loadPerformance($employees);
    }

    public function updatedSearch(EmployeeRepositoryInterface $employees): void
    {
        $this->loadPerformance($employees);
    }

    public function updatedDate(EmployeeRepositoryInterface $employees): void
    {
        $this->loadPerformance($employees);
    }

    /** @return list<CarbonInterface> */
    private function resolveDates(CarbonInterface $carbonDate): array
    {
        return match ($this->periodType) {
            'weekly' => collect(range(0, 6))->map(fn ($i) => $carbonDate->copy()->startOfWeek()->addDays($i))->all(),
            'monthly' => collect(range(0, $carbonDate->daysInMonth - 1))->map(fn ($i) => $carbonDate->copy()->startOfMonth()->addDays($i))->all(),
            default => [$carbonDate],
        };
    }

    private function isPowerUser(): bool
    {
        return (bool) auth()->user()->can('viewAny', CallRecord::class);
    }

    public function loadPerformance(EmployeeRepositoryInterface $employees): void
    {
        $action = app(GetStandardizedPerformanceAction::class);
        $carbonDate = Carbon::parse($this->date);
        $user = auth()->user();
        $me = $employees->findByUser($user->id);
        $isPowerUser = $this->isPowerUser();

        $data = [];

        if ($this->employeeId) {
            $employee = $employees->find($this->employeeId);

            // Validar acceso al empleado solicitado
            if (! $employee || (! $isPowerUser && ! $user->can('viewPerformance', $employee))) {
                $this->employeeId = $me ? (int) $me->getId() : null;
                $employee = $me;
            }

            if ($employee) {
                foreach ($this->resolveDates($carbonDate) as $date) {
                    $performance = $this->cachedExecute($action, $employee, $date);
                    $performance['employee'] = [
                        'full_name' => $employee->getFullName(),
                        'avatar' => $employee->avatar_url,
                    ];
                    $data[] = $performance;
                }
            }
        } else {
            $employeesList = $employees->findAgentsByPositions(
                self::POSITION_IDS,
                $this->teamId,
                $this->search,
            );

            if (! $isPowerUser) {
                $managedTeamIds = $me?->getManagedTeamIds() ?? [];
                $employeesList = array_values(array_filter(
                    $employeesList,
                    fn (EmployeeInterface $e) => in_array($e->getTeamId(), $managedTeamIds, true),
                ));
            }

            foreach ($employeesList as $employee) {
                $performance = $this->cachedExecute($action, $employee, $carbonDate);
                $performance['employee'] = [
                    'full_name' => $employee->getFullName(),
                    'avatar' => $employee->avatar_url,
                ];
                $data[] = $performance;
            }
        }

        $this->performanceData = $data;
    }

    private function cachedExecute(
        GetStandardizedPerformanceAction $action,
        EmployeeInterface $employee,
        CarbonInterface $date,
    ): array {
        $cacheKey = "wfm:scorecard:{$employee->getId()}:{$date->toDateString()}";

        return Cache::remember($cacheKey, 300, fn () => $action->execute($employee, $date)->toArray());
    }

    public function formatMinutes(float $minutes): string
    {
        $seconds = (int) round($minutes * 60);
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    public function render(EmployeeRepositoryInterface $employees)
    {
        $user = auth()->user();
        $employee = $employees->findByUser($user->id);
        $isPowerUser = $this->isPowerUser();

        $managedTeamIds = $employee?->getManagedTeamIds() ?? [];

        $teams = $isPowerUser
            ? Team::all()
            : Team::whereIn('id', $managedTeamIds)->get();

        $employeesList = collect($employees->findAgentsByPositions(self::POSITION_IDS, $this->teamId));
        $selectedEmployee = $this->employeeId ? $employees->find($this->employeeId) : null;

        return view('operations::livewire.performance-scorecard', [
            'teams' => $teams,
            'employees' => $employeesList,
            'selectedEmployee' => $selectedEmployee,
        ]);
    }
}
