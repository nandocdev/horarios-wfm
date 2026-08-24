<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\ConnectModule\Models\CallRecord;
use App\Shared\Support\Metrics\ServiceQualityMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class SlaAsaChartWidget extends Component
{
    public array $employeeIds = [];

    public string $selectedDate;

    public function placeholder()
    {
        return '<div class="h-64 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    /**
     * SLA y ASA por hora (06:00-17:00) para hoy y ayer.
     * Una única consulta por día agrupada por hora en lugar de 12 por día:
     * el widget se refresca cada 120 s vía wire:poll.
     *
     * @return array{hours: array<int, object>, has_data: bool}
     */
    private function hourlyStats(string $date): array
    {
        $rows = CallRecord::query()
            ->whereDate('ivr_started_at', $date)
            ->whereBetween(DB::raw('EXTRACT(HOUR FROM ivr_started_at)'), [6, 17])
            ->groupBy(DB::raw('EXTRACT(HOUR FROM ivr_started_at)'))
            ->get([
                DB::raw('EXTRACT(HOUR FROM ivr_started_at) as hour'),
                DB::raw('COUNT(*) as total_offered'),
                DB::raw('AVG(CASE WHEN contact_disposition = 2 THEN queue_time END) as avg_asa'),
                DB::raw('SUM(CASE WHEN contact_disposition = 2 AND queue_time <= 20 THEN 1 ELSE 0 END) as sla_count'),
            ])
            ->keyBy(fn ($row) => (int) $row->hour);

        return ['hours' => $rows, 'has_data' => $rows->isNotEmpty()];
    }

    public function mount(string $selectedDate): void
    {
        $this->selectedDate = $selectedDate;
    }

    public function render()
    {
        $today = $this->selectedDate;
        $yesterday = Carbon::parse($today)->subDay()->toDateString();

        $todayStats = $this->hourlyStats($today);
        $yesterdayStats = $this->hourlyStats($yesterday);
        $hasData = $todayStats['has_data'] || $yesterdayStats['has_data'];

        $todaySla = [];
        $todayAsa = [];
        $yesterdaySla = [];
        $yesterdayAsa = [];
        $categories = [];

        foreach (range(6, 17) as $h) {
            $categories[] = sprintf('%02d:00', $h);

            $t = $todayStats['hours']->get($h);
            $todaySla[] = $t ? ServiceQualityMetrics::serviceLevel((int) $t->sla_count, (int) $t->total_offered) : 0;
            $todayAsa[] = $t && $t->avg_asa !== null ? round((float) $t->avg_asa) : 0;

            $y = $yesterdayStats['hours']->get($h);
            $yesterdaySla[] = $y ? ServiceQualityMetrics::serviceLevel((int) $y->sla_count, (int) $y->total_offered) : 0;
            $yesterdayAsa[] = $y && $y->avg_asa !== null ? round((float) $y->avg_asa) : 0;
        }

        if (! $hasData) {
            // Sin datos no se dibuja una gráfica de ceros que simule SLA catastrófico.
            return view('operations::livewire.control-tower.sla-asa-chart-widget', [
                'hasData' => false,
                'chartOptions' => [],
            ]);
        }

        return view('operations::livewire.control-tower.sla-asa-chart-widget', [
            'hasData' => true,
            'chartOptions' => [
                'chart' => ['type' => 'line', 'toolbar' => ['show' => false], 'zoom' => ['enabled' => false], 'fontFamily' => 'inherit', 'height' => 220],
                'series' => [
                    ['name' => 'SLA Hoy', 'data' => $todaySla],
                    ['name' => 'ASA Hoy (s)', 'data' => $todayAsa],
                    ['name' => 'SLA Ayer', 'data' => $yesterdaySla],
                    ['name' => 'ASA Ayer (s)', 'data' => $yesterdayAsa],
                ],
                'xaxis' => ['categories' => $categories, 'labels' => ['style' => ['fontSize' => '10px']]],
                'colors' => ['#22c55e', '#3b82f6', '#86efac', '#93c5fd'],
                'stroke' => ['width' => [2, 1.5, 2, 1.5], 'dashArray' => [0, 0, 4, 4], 'curve' => 'smooth'],
                'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 2],
                'dataLabels' => ['enabled' => false],
                'legend' => ['show' => true, 'position' => 'top', 'fontSize' => '10px'],
                'tooltip' => ['shared' => true],
            ],
        ]);
    }
}
