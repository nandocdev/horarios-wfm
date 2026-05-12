<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
            'today' => [Carbon::today(), Carbon::tomorrow()],
            'this_week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            default => [Carbon::today(), Carbon::tomorrow()],
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

        $query = CallRecord::whereBetween('ivr_started_at', [$start, $end]);

        if (is_array($allowedIds)) {
            $query->whereIn('employee_id', $allowedIds);
        }

        // [PERFORMANCE] Cálculos macro en una sola consulta
        $stats = $query->select(
            DB::raw('COUNT(*) as total_volume'),
            DB::raw('SUM(CASE WHEN status = \'closed\' THEN 1 ELSE 0 END) as total_handled'),
            DB::raw('SUM(CASE WHEN status = \'abandoned\' OR contact_disposition = 1 THEN 1 ELSE 0 END) as total_abandoned'),
            DB::raw('SUM(CASE WHEN status = \'closed\' AND queue_time <= 20 THEN 1 ELSE 0 END) as calls_within_sla') // Asumiendo SLA de 20s
        )->first();

        $totalVolume = (int) ($stats->total_volume ?? 0);
        $totalHandled = (int) ($stats->total_handled ?? 0);
        $totalAbandoned = (int) ($stats->total_abandoned ?? 0);
        $callsWithinSla = (int) ($stats->calls_within_sla ?? 0);

        return [
            'total_volume' => $totalVolume,
            'abandon_rate' => $totalVolume > 0 ? round(($totalAbandoned / $totalVolume) * 100, 1) : 0,
            'sla' => $totalVolume > 0 ? round(($callsWithinSla / $totalVolume) * 100, 1) : 0,
            'total_handled' => $totalHandled,
        ];
    }

    #[Computed]
    public function topPerformers()
    {
        [$start, $end] = $this->dateBoundaries();
        $allowedIds = $this->allowedEmployeeIds();

        if (is_array($allowedIds) && empty($allowedIds)) {
            return collect();
        }

        $query = CallRecord::with('employee:id,first_name,last_name')
            ->whereBetween('ivr_started_at', [$start, $end])
            ->whereNotNull('employee_id')
            ->where('status', 'closed');

        if (is_array($allowedIds)) {
            $query->whereIn('employee_id', $allowedIds);
        }

        return $query->select('employee_id', DB::raw('COUNT(*) as total_calls'), DB::raw('AVG(talk_time) as avg_tmo'))
            ->groupBy('employee_id')
            ->orderByDesc('total_calls')
            ->limit(5)
            ->get();
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
