<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Models\Team;
use Livewire\Component;

class StaffingSummary extends Component
{
    public array $stats = [];

    public array $byTeam = [];

    public array $byPosition = [];

    public array $byStatus = [];

    public function mount()
    {
        $this->authorize('reports.staffing');
        $this->loadData();
    }

    public function loadData()
    {
        // Totales base
        $this->stats = [
            'total' => Employee::count(),
            'active' => Employee::active()->count(),
            'inactive' => Employee::where('is_active', false)->count(),
            'managers' => Employee::where('is_manager', true)->count(),
        ];

        // Por Equipo
        $this->byTeam = Team::withCount(['employees' => function ($q) {
            $q->where('is_active', true);
        }])
            ->active()
            ->orderBy('employees_count', 'desc')
            ->get()
            ->toArray();

        // Por Posición
        $this->byPosition = Position::withCount(['employees' => function ($q) {
            $q->where('is_active', true);
        }])
            ->orderBy('employees_count', 'desc')
            ->get()
            ->toArray();

        // Por Estatus de Empleo
        $this->byStatus = EmploymentStatus::withCount(['employees' => function ($q) {
            $q->where('is_active', true);
        }])
            ->get()
            ->toArray();
    }

    public function render()
    {
        return view('personnel::livewire.staffing-summary')
            ->layout('layouts.app', ['title' => 'Inventario de Staffing']);
    }
}
