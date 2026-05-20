<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WorkflowsModule\Models\LeaveRequest;
use App\Modules\WorkflowsModule\Models\ShiftSwapRequest;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RequestSummary extends Component
{
    public array $leaveStats = [];

    public array $swapStats = [];

    public array $byType = [];

    public function mount()
    {
        $this->authorize('reports.requests');
        $this->loadData();
    }

    public function loadData()
    {
        // Estadísticas de Permisos
        $this->leaveStats = [
            'total' => LeaveRequest::count(),
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];

        // Estadísticas de Cambios de Turno
        $this->swapStats = [
            'total' => ShiftSwapRequest::count(),
            'pending' => ShiftSwapRequest::where('status', 'pending')->count(),
            'approved' => ShiftSwapRequest::where('status', 'approved')->count(),
            'rejected' => ShiftSwapRequest::where('status', 'rejected')->count(),
        ];

        // Distribución por Tipo de Permiso
        $this->byType = LeaveRequest::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    public function render()
    {
        return view('wfm::livewire.request-summary')
            ->layout('layouts.app', ['title' => 'Resumen de Solicitudes WFM']);
    }
}
