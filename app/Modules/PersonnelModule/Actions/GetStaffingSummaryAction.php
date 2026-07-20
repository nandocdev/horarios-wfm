<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Models\Team;

class GetStaffingSummaryAction
{
    public function execute(): array
    {
        return [
            'stats' => $this->getStats(),
            'byTeam' => $this->getByTeam(),
            'byPosition' => $this->getByPosition(),
            'byStatus' => $this->getByStatus(),
        ];
    }

    private function getStats(): array
    {
        return [
            'total' => Employee::count(),
            'active' => Employee::active()->count(),
            'inactive' => Employee::where('is_active', false)->count(),
            'managers' => Employee::where('is_manager', true)->count(),
        ];
    }

    private function getByTeam(): array
    {
        return Team::withCount(['employees' => fn ($q) => $q->where('is_active', true)])
            ->active()
            ->orderBy('employees_count', 'desc')
            ->get()
            ->toArray();
    }

    private function getByPosition(): array
    {
        return Position::withCount(['employees' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('employees_count', 'desc')
            ->get()
            ->toArray();
    }

    private function getByStatus(): array
    {
        return EmploymentStatus::withCount(['employees' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->toArray();
    }
}
