<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\OperationsModule\Models\AgentDailyMetric;
use App\Modules\PersonnelModule\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class AdvancedProductivityDashboard extends Component
{
    #[Url]
    public string $date = '';

    #[Url]
    public ?int $teamId = null;

    public function mount()
    {
        $this->date = $this->date ?: now()->toDateString();
    }

    public function render()
    {
        $metricsQuery = AgentDailyMetric::with(['employee.team', 'employee.position'])
            ->where('metric_date', $this->date);

        if ($this->teamId) {
            $metricsQuery->whereHas('employee', function($q) {
                $q->where('team_id', $this->teamId);
            });
        }

        $metrics = $metricsQuery->get();

        $summary = [
            'avg_pwi' => $metrics->avg('pwi_pct') ?? 0,
            'avg_availability' => $metrics->avg('availability_pct') ?? 0,
            'avg_efficiency' => $metrics->avg('efficiency_pct') ?? 0,
            'total_work_units' => $metrics->sum('work_units'),
            'total_capacity_calls' => $metrics->sum('capacity_calls'),
            'total_actual_calls' => $metrics->sum('calls_total'),
            'total_gap' => $metrics->sum('capacity_gap'),
        ];

        // Ranking de mejores PWI
        $topPerformers = $metrics->sortByDesc('pwi_pct')->take(5);
        
        // Ranking de mayores Gaps (oportunidad de mejora)
        $underPerformers = $metrics->sortByDesc('capacity_gap')->take(5);

        return view('operations::livewire.advanced-productivity-dashboard', [
            'metrics' => $metrics->sortByDesc('pwi_pct'),
            'summary' => $summary,
            'teams' => Team::all(),
            'topPerformers' => $topPerformers,
            'underPerformers' => $underPerformers,
        ]);
    }
}
