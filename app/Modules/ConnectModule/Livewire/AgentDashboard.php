<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Actions\GetAgentDashboardDataAction;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\PersonnelModule\Models\Employee;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class AgentDashboard extends Component
{
    use WithPagination;

    public string $dateRange = 'today';

    public function mount(): void
    {
        $this->authorize('viewAny', CallRecord::class);
    }

    #[Computed]
    public function employee(): ?Employee
    {
        return auth()->user()?->employee;
    }

    #[Computed]
    public function metrics(): array
    {
        if (! $this->employee) {
            return [
                'total_calls' => 0,
                'avg_talk_time' => 0,
                'avg_handle_time' => 0,
                'abandoned' => 0,
            ];
        }

        return app(GetAgentDashboardDataAction::class)->execute($this->employee, $this->dateRange);
    }

    #[Computed]
    public function recentCalls()
    {
        if (! $this->employee) {
            return collect();
        }

        return app(GetAgentDashboardDataAction::class)->getRecentCalls($this->employee, $this->dateRange);
    }

    public function render()
    {
        return view('connect::livewire.agent-dashboard');
    }
}
