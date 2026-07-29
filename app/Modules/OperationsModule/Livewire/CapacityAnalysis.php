<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\AnalyticsModule\Models\CapacityPlan;
use App\Modules\AnalyticsModule\Models\CapacityResult;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CapacityAnalysis extends Component
{
    public function render()
    {
        $latestPlans = CapacityPlan::with('results')
            ->orderByDesc('plan_date')
            ->limit(20)
            ->get();

        $globalStats = CapacityResult::join('capacity_plans', 'capacity_results.capacity_plan_id', '=', 'capacity_plans.id')
            ->select(
                DB::raw('COUNT(DISTINCT capacity_plans.id) as total_plans'),
                DB::raw('COUNT(DISTINCT capacity_results.queue_id) as total_queues'),
                DB::raw('AVG(capacity_results.avg_coverage) as avg_coverage'),
                DB::raw('SUM(capacity_results.total_staff_required) as total_required'),
                DB::raw('SUM(capacity_results.total_staff_available) as total_available'),
                DB::raw('SUM(capacity_results.intervals_with_gap) as total_gap_intervals'),
                DB::raw('SUM(capacity_results.total_intervals) as total_intervals'),
            )
            ->first();

        $coverageTrend = CapacityPlan::with('results')
            ->orderBy('plan_date')
            ->get()
            ->mapWithKeys(fn ($p) => [$p->plan_date?->format('d/m') => $p->results->avg('avg_coverage')])
            ->filter();

        return view('operations::livewire.capacity-analysis', [
            'latestPlans' => $latestPlans,
            'globalStats' => $globalStats,
            'coverageTrend' => $coverageTrend,
            'totalPlans' => $globalStats?->total_plans ?? 0,
            'totalQueues' => $globalStats?->total_queues ?? 0,
            'avgCoverage' => $globalStats?->avg_coverage ?? 0,
            'totalRequired' => $globalStats?->total_required ?? 0,
            'totalAvailable' => $globalStats?->total_available ?? 0,
            'gapPct' => ($globalStats && $globalStats->total_intervals > 0)
                ? round(($globalStats->total_gap_intervals / $globalStats->total_intervals) * 100, 1)
                : 0,
        ])->layout('layouts.app', ['title' => 'Análisis de Capacidad']);
    }
}
