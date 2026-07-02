<?php

declare(strict_types=1);

namespace App\Src\Wfm\Presentation\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Src\Wfm\Infrastructure\Services\CachedIntradayService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Mi Día')]
class MyDay extends Component
{
    public ?int $targetEmployeeId = null;
    public array $state = [];

    public function mount(): void
    {
        $user = auth()->user();
        $employee = $user?->employee;
        if (! $employee) abort(403);

        $this->targetEmployeeId = $employee->id;
        $this->refreshState();
    }

    public function refreshState(): void
    {
        $service = app(CachedIntradayService::class);
        $this->state = $service->getAgentState($this->targetEmployeeId);
    }

    public function render()
    {
        $employee = Employee::find($this->targetEmployeeId);

        return view('wfm-src::livewire.my-day', [
            'employee' => $employee,
        ]);
    }
}
