<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class MyDay extends Component
{
    public $targetEmployeeId;

    public $targetTeamId;

    public $stats = [];

    public $isManager = false;

    public $availableTeams = [];

    public $availableEmployees = [];

    public function mount()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403, 'No tienes un perfil de empleado asociado.');
        }

        $this->isManager = $user->hasAnyRole(['admin', 'wfm', 'supervisor', 'coordinator', 'chief']);
        $this->targetEmployeeId = $employee->id;

        if ($this->isManager) {
            $this->loadFilterData($employee, $user);
        }

        $this->loadStats();
    }

    protected function loadFilterData($employee, $user)
    {
        $isPowerUser = $user->hasAnyRole(['admin', 'wfm']);

        if ($isPowerUser) {
            $this->availableTeams = Team::active()->orderBy('name')->get();
        } else {
            $managedTeamIds = $employee->getManagedTeamIds();
            $this->availableTeams = Team::whereIn('id', $managedTeamIds)->active()->orderBy('name')->get();
        }

        $this->updatedTargetTeamId($this->targetTeamId);
    }

    public function updatedTargetTeamId($teamId)
    {
        $user = Auth::user();
        $employee = $user->employee;
        $isPowerUser = $user->hasAnyRole(['admin', 'wfm']);

        $query = Employee::active()->orderBy('first_name');

        if ($teamId) {
            $query->where('team_id', $teamId);
        } else {
            if (! $isPowerUser) {
                $subordinateIds = $employee->getAllSubordinateIds();
                $managedTeamIds = $employee->getManagedTeamIds();
                $managedTeamMemberIds = Employee::whereIn('team_id', $managedTeamIds)->pluck('id')->toArray();
                $allVisibleIds = array_unique(array_merge($subordinateIds, $managedTeamMemberIds, [$employee->id]));
                $query->whereIn('id', $allVisibleIds);
            }
        }

        $this->availableEmployees = $query->get();
    }

    public function updatedTargetEmployeeId()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $employeeId = $this->targetEmployeeId;
        $date = now()->toDateString();

        $this->stats = Cache::remember("stats:{$employeeId}:{$date}", 60, function () use ($employeeId, $date) {
            $transitions = AgentStateTransition::where('employee_id', $employeeId)
                ->whereDate('transition_time', $date)
                ->get();

            $totalSeconds = 0;
            $productiveSeconds = 0;
            $availableSeconds = 0;

            foreach ($transitions as $t) {
                if ($t->duration) {
                    $totalSeconds += $t->duration;
                    $state = strtoupper(trim((string) $t->agent_state));

                    if (in_array($state, ['READY', 'RESERVED', 'TALKING', 'WORK', 'HOLD', 'OUTBOUND'])) {
                        if (in_array($state, ['RESERVED', 'TALKING', 'WORK', 'HOLD', 'OUTBOUND'])) {
                            $productiveSeconds += $t->duration;
                        }
                        if ($state === 'READY') {
                            $availableSeconds += $t->duration;
                        }
                    }
                }
            }

            $effectiveTime = $productiveSeconds + $availableSeconds;

            return [
                'total_time' => sprintf('%02dh %02dm', floor($totalSeconds / 3600), floor(($totalSeconds % 3600) / 60)),
                'productive_time' => sprintf('%02dh %02dm', floor($productiveSeconds / 3600), floor(($productiveSeconds % 3600) / 60)),
                'occupancy' => $effectiveTime > 0 ? round(($productiveSeconds / $effectiveTime) * 100) : 0,
            ];
        });
    }

    public function render()
    {
        $targetEmployee = Employee::find($this->targetEmployeeId);

        return view('wfm::livewire.my-day', [
            'targetEmployee' => $targetEmployee,
        ])->layout('layouts.app');
    }
}
