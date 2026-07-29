<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Livewire\Component;

class ControlTowerDashboard extends Component
{
    public string $selectedDate;

    public string $scope = 'all';

    public int $refreshInterval = 60;

    public ?int $teamId = null;

    protected function getCurrentUser()
    {
        return auth()->user();
    }

    protected function getEmployee()
    {
        return $this->getCurrentUser()?->employee;
    }

    public function getUserRole(): string
    {
        $user = $this->getCurrentUser();
        $employee = $this->getEmployee();

        if (! $user) {
            return 'guest';
        }
        if ($user->can('viewAny', CallRecord::class)) {
            return 'wfm';
        }
        if ($employee && ($user->hasRole('chief') || $employee->hasCoordinatorRights())) {
            return 'chief';
        }
        if ($employee && ($employee->is_manager || $user->hasRole(['supervisor', 'coordinator']))) {
            return 'supervisor';
        }
        if ($employee) {
            return 'operator';
        }

        return 'guest';
    }

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function updatedScope($value): void
    {
        $this->scope = $value;
        $this->dispatch('control-tower-scope-changed', scope: $value);
    }

    public function updatedTeamId($value): void
    {
        $this->teamId = $value ? (int) $value : null;
        $this->dispatch('control-tower-team-changed', teamId: $this->teamId);
    }

    public function refreshAll(): void
    {
        $this->dispatch('control-tower-refresh');
    }

    public function render()
    {
        $user = $this->getCurrentUser();
        $employee = $this->getEmployee();
        $now = now();
        $role = $this->getUserRole();

        $today = $now->toDateString();
        $employeeIds = $this->resolveEmployeeIds();

        $displayName = $employee?->full_name ?? $user?->name ?? 'Operador';
        $roleLabel = match ($role) {
            'wfm' => 'Analista WFM',
            'chief' => 'Jefe',
            'supervisor' => 'Supervisor',
            'operator' => 'Operador',
            default => 'Usuario',
        };
        $greeting = match (true) {
            $now->hour < 12 => 'Buenos días',
            $now->hour < 19 => 'Buenas tardes',
            default => 'Buenas noches',
        };

        $teams = [];
        if (in_array($role, ['wfm', 'chief'])) {
            $teams = Team::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('operations::livewire.control-tower.control-tower-dashboard', [
            'greeting' => $greeting,
            'displayName' => $displayName,
            'roleLabel' => $roleLabel,
            'role' => $role,
            'todayLabel' => $now->locale('es')->translatedFormat('l d F Y'),
            'currentTime' => $now->format('H:i'),
            'selectedDate' => $this->selectedDate,
            'scope' => $this->scope,
            'refreshInterval' => $this->refreshInterval,
            'teamId' => $this->teamId,
            'teams' => $teams,
            'employeeIds' => $employeeIds,
        ])->layout('layouts.app', ['title' => 'Centro de Control']);
    }

    protected function resolveEmployeeIds(): array
    {
        if ($this->scope === 'team' && $this->teamId) {
            return Employee::where('team_id', $this->teamId)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
        }

        return Employee::where('is_active', true)->pluck('id')->toArray();
    }
}
