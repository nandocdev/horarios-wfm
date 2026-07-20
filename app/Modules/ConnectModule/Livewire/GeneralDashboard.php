<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Actions\GetGeneralDashboardDataAction;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Services\CallCenterAnalyticsService;
use App\Modules\PersonnelModule\Models\Employee;
use Livewire\Attributes\Computed;
use Livewire\Component;

class GeneralDashboard extends Component
{
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
            return $this->emptyMetrics();
        }

        return app(GetGeneralDashboardDataAction::class)
            ->getMetrics($this->employee, $this->dateRange, app(CallCenterAnalyticsService::class));
    }

    #[Computed]
    public function topPerformers(): array
    {
        if (! $this->employee) {
            return [];
        }

        return app(GetGeneralDashboardDataAction::class)
            ->getTopPerformers($this->employee, $this->dateRange, app(CallCenterAnalyticsService::class));
    }

    private function emptyMetrics(): array
    {
        return [
            'total_volume' => 0,
            'abandon_rate' => 0,
            'sla' => 0,
            'total_handled' => 0,
        ];
    }

    public function render()
    {
        return view('connect::livewire.general-dashboard');
    }
}
