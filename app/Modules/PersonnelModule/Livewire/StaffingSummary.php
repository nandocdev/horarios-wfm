<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\GetStaffingSummaryAction;
use App\Modules\PersonnelModule\Models\Employee;
use Livewire\Component;

class StaffingSummary extends Component
{
    public array $stats = [];

    public array $byTeam = [];

    public array $byPosition = [];

    public array $byStatus = [];

    public function mount(GetStaffingSummaryAction $action): void
    {
        $this->authorize('viewAny', Employee::class);

        $summary = $action->execute();
        $this->stats = $summary['stats'];
        $this->byTeam = $summary['byTeam'];
        $this->byPosition = $summary['byPosition'];
        $this->byStatus = $summary['byStatus'];
    }

    public function render()
    {
        return view('personnel::livewire.staffing-summary')
            ->layout('layouts.app', ['title' => 'Inventario de Staffing']);
    }
}
