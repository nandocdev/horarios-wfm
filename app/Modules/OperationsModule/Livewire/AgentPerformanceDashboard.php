<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\OperationsModule\Services\AgentPerformanceService;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AgentPerformanceDashboard extends Component
{
    public ?int $employeeId = null;

    public int $days = 5;

    public array $selectableEmployees = [];

    public function mount(?int $employee = null): void
    {
        $user = auth()->user();
        $myEmployeeId = $user?->employee?->id;

        if ($employee && $employee !== $myEmployeeId) {
            Gate::allowIf(fn () => $user?->can('agent.performance.view'));
        }

        $this->employeeId = $employee ?? $myEmployeeId;

        if (!$this->employeeId) {
            session()->now('warning', 'No hay un empleado asociado a tu usuario. Contacta a un administrador.');
        }

        // Cargar empleados seleccionables para supervisores
        if ($user?->can('agent.performance.view')) {
            $this->selectableEmployees = Employee::where('is_active', true)
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(fn ($e) => [$e->id => $e->first_name . ' ' . $e->last_name])
                ->toArray();
        }
    }

    #[Computed]
    public function employee(): ?Employee
    {
        return Employee::with(['team', 'position', 'status'])->find($this->employeeId);
    }

    #[Computed]
    public function performance(): array
    {
        $employee = $this->employee;
        if (!$employee) {
            return [];
        }

        $service = app(AgentPerformanceService::class);
        $result = $service->getPerformance($employee, $this->days);

        return [
            'summary' => $result->summary,
            'days' => $result->days,
            'stateDistribution' => $result->stateDistribution,
            'queueDetail' => $result->queueDetail,
            'deviations' => $result->deviations,
            'dailyAdherence' => array_map(fn ($d) => $d['metrics']['productivity_percentage'] ?? 0, $result->days),
            'dailyOccupancy' => array_map(fn ($d) => $d['metrics']['utilization_percentage'] ?? 0, $result->days),
            'dailyLabels' => array_map(fn ($d) => \Carbon\Carbon::parse($d['date'])->locale('es')->isoFormat('ddd D/M'), $result->days),
            'dailyCalls' => array_map(fn ($d) => array_sum(array_column($d['queues'] ?? [], 'total_calls')), $result->days),
        ];
    }

    public function render()
    {
        return view('operations::livewire.agent-performance-dashboard')
            ->layout('layouts.app', ['title' => 'Mi Desempeño']);
    }
}
