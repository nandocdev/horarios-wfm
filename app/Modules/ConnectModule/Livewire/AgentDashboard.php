<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente interactivo para "Mis Datos" (Agent Dashboard).
 * Muestra métricas de rendimiento individual del agente autenticado.
 */
class AgentDashboard extends Component
{
    use WithPagination;

    public string $dateRange = 'today'; // today, this_week, this_month

    public function mount(): void
    {
        // En una app real de producción, el acceso debería restringirse mediante policies:
        // $this->authorize('viewAgentDashboard', CallRecord::class);
    }

    #[Computed]
    public function employee(): ?Employee
    {
        // Asume que el usuario autenticado tiene relación con empleado
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

    #[Computed]
    public function metrics(): array
    {
        if (! $this->employee) {
            return [
                'total_calls' => 0,
                'avg_talk_time' => 0, // TMO
                'avg_handle_time' => 0, // AHT
                'abandoned' => 0,
            ];
        }

        [$start, $end] = $this->dateBoundaries();

        // [PERFORMANCE] Usar agregación en BD para evitar hidratación masiva
        $stats = CallRecord::where('employee_id', $this->employee->id)
            ->whereBetween('ivr_started_at', [$start, $end])
            ->select(
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('SUM(CASE WHEN contact_disposition = 1 OR status = \'abandoned\' THEN 1 ELSE 0 END) as abandoned'),
                DB::raw('AVG(talk_time) as avg_talk_time'),
                DB::raw('AVG(talk_time + work_time) as avg_handle_time')
            )->first();

        return [
            'total_calls' => (int) ($stats->total_calls ?? 0),
            'abandoned' => (int) ($stats->abandoned ?? 0),
            'avg_talk_time' => round((float) ($stats->avg_talk_time ?? 0), 0),
            'avg_handle_time' => round((float) ($stats->avg_handle_time ?? 0), 0),
        ];
    }

    #[Computed]
    public function recentCalls()
    {
        if (! $this->employee) {
            return collect();
        }

        [$start, $end] = $this->dateBoundaries();

        return CallRecord::with(['queue', 'caseSubtype'])
            ->where('employee_id', $this->employee->id)
            ->whereBetween('ivr_started_at', [$start, $end])
            ->orderByDesc('ivr_started_at')
            ->paginate(10);
    }

    public function render()
    {
        return view('connect::livewire.agent-dashboard');
    }
}
