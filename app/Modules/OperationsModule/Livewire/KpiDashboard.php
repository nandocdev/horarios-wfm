<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\AnalyticsModule\Models\DailyKpi;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Carbon\Carbon;
use Livewire\Component;

class KpiDashboard extends Component
{
    public string $date = '';

    public string $granularity = 'global';

    public ?int $teamId = null;

    public ?int $employeeId = null;

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function updatedGranularity(): void
    {
        $this->teamId = null;
        $this->employeeId = null;
    }

    public function render()
    {
        $kpi = null;
        $teams = collect();
        $employees = collect();
        $rows = collect();

        if ($this->granularity === 'global') {
            $kpi = DailyKpi::where('evaluation_date', $this->date)
                ->where('granularity', 'global')
                ->first();
        } elseif ($this->granularity === 'team') {
            $teams = Team::orderBy('name')->get(['id', 'name']);

            if ($this->teamId) {
                $kpi = DailyKpi::where('evaluation_date', $this->date)
                    ->where('granularity', 'team')
                    ->where('dim_team_id', $this->teamId)
                    ->first();
            }

            $rows = DailyKpi::where('evaluation_date', $this->date)
                ->where('granularity', 'team')
                ->whereNotNull('dim_team_id')
                ->get();
        } elseif ($this->granularity === 'employee') {
            $employees = Employee::where('is_active', true)
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'team_id']);

            if ($this->employeeId) {
                $kpi = DailyKpi::where('evaluation_date', $this->date)
                    ->where('granularity', 'employee')
                    ->where('dim_employee_id', $this->employeeId)
                    ->first();
            }

            $rows = DailyKpi::where('evaluation_date', $this->date)
                ->where('granularity', 'employee')
                ->whereNotNull('dim_employee_id')
                ->get()
                ->keyBy('dim_employee_id');
        }

        return view('operations::livewire.kpi-dashboard', [
            'kpi' => $kpi,
            'teams' => $teams,
            'employees' => $employees,
            'rows' => $rows,
        ])->layout('layouts.app', ['title' => 'KPIs - '.Carbon::parse($this->date)->format('d/m/Y')]);
    }
}
