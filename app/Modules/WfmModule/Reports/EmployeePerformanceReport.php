<?php

declare(strict_types=1);

namespace App\Reports;

use App\Modules\OperationsModule\Services\AgentPerformanceService;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Contracts\View\View;

class EmployeePerformanceReport extends BaseReport
{
    public function __construct(
        private readonly Employee $employee,
        private readonly int $days = 5,
    ) {
        parent::__construct();
        $this->title = 'Reporte de Desempeño Individual';
        $this->footer = [
            'left' => 'WFM CSS — Reporte de Desempeño Individual',
            'right' => 'Generado: {date}',
        ];
    }

    public function data(): array
    {
        $service = app(AgentPerformanceService::class);
        $perf = $service->getPerformance($this->employee, $this->days);

        return [
            'employee' => $this->employee,
            'days' => $this->days,
            'summary' => $perf->summary,
            'daily' => $perf->days,
            'stateDistribution' => $perf->stateDistribution,
            'queueDetail' => $perf->queueDetail,
            'deviations' => $perf->deviations,
            'showAllDeviations' => true,
        ];
    }

    public function view(): View
    {
        return view('reports.employee-performance-report', $this->data());
    }
}
