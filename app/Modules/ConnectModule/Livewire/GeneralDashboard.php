<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Services\CallCenterAnalyticsService;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Componente interactivo para el Dashboard General (Vista Supervisor/Admin).
 * Muestra métricas agregadas de la operación aplicando filtros de seguridad jerárquica.
 */
class GeneralDashboard extends Component
{
    public string $dateRange = 'today'; // today, this_week, this_month

    public function mount(): void
    {
        // En producción: $this->authorize('viewGeneralDashboard', CallRecord::class);
    }

    #[Computed]
    public function employee(): ?Employee
    {
        return auth()->user()?->employee;
    }

    #[Computed]
    public function dateBoundaries(): array
    {
        return match ($this->dateRange) {
            'today' => [Carbon::today()->toDateString(), Carbon::tomorrow()->toDateString()],
            'this_week' => [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->addDay()->toDateString()],
            'this_month' => [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->endOfMonth()->addDay()->toDateString()],
            default => [Carbon::today()->toDateString(), Carbon::tomorrow()->toDateString()],
        };
    }

    /**
     * Obtiene los IDs de los empleados que este usuario tiene permiso para ver.
     * Si es Admin/Director, devuelve null (sin filtro). Si es Coordinador, devuelve sus subordinados.
     */
    #[Computed]
    public function allowedEmployeeIds(): ?array
    {
        if (! $this->employee) {
            return [];
        }

        // Si el usuario tiene un rol global de director/admin, ver todo
        if (auth()->user()?->hasRole(['super-admin', 'director'])) {
            return null; // Null significa "sin restricción"
        }

        // Obtener subordinados recursivos usando el Adjacency List
        $subordinateIds = $this->employee->getAllSubordinateIds();

        // Incluirse a sí mismo por si atiende llamadas
        $subordinateIds[] = $this->employee->id;

        return $subordinateIds;
    }

    #[Computed]
    public function metrics(): array
    {
        [$start, $end] = $this->dateBoundaries();
        $allowedIds = $this->allowedEmployeeIds();

        if (is_array($allowedIds) && empty($allowedIds)) {
            return $this->emptyMetrics();
        }

        $service = app(CallCenterAnalyticsService::class);

        return $service->getSummaryMetrics(
            dateFrom: $start,
            dateTo: $end,
            employeeIds: $allowedIds,
        );
    }

    #[Computed]
    public function topPerformers(): array
    {
        [$start, $end] = $this->dateBoundaries();
        $allowedIds = $this->allowedEmployeeIds();

        if (is_array($allowedIds) && empty($allowedIds)) {
            return [];
        }

        $service = app(CallCenterAnalyticsService::class);

        $rows = $service->getTopAgentsToday(
            limit: 5,
            dateFrom: $start,
            dateTo: $end,
            employeeIds: $allowedIds,
        );

        return array_map(fn ($row) => (object) [
            'employee' => (object) ['full_name' => $row->agent_name],
            'total_calls' => (int) $row->total_calls,
            'avg_tmo' => (float) $row->avg_talk_time,
        ], $rows);
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
