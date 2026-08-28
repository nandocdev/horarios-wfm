<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\AnalyticsModule\Models\DailyKpi;
use App\Modules\OperationsModule\Models\AgentDailyMetric;
use App\Modules\OperationsModule\Models\QueueDailyMetric;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Shared\Support\CallQueueCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ComparisonDashboard extends Component
{
    public string $dimension = 'team';

    public string $dateFrom = '';

    public string $dateTo = '';

    public array $selectedIds = [];

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(7)->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function updatedDimension(): void
    {
        $this->selectedIds = [];
    }

    public function toggleId(int|string $id): void
    {
        $id = (int) $id;
        $current = array_map('intval', $this->selectedIds);

        if (in_array($id, $current, true)) {
            $this->selectedIds = array_values(array_filter($current, fn ($i) => $i !== $id));
        } else {
            $this->selectedIds = array_values(array_unique(array_merge($current, [$id])));
        }
    }

    public function selectAll(array $ids): void
    {
        $this->selectedIds = array_values(array_map('intval', $ids));
    }

    public function deselectAll(): void
    {
        $this->selectedIds = [];
    }

    public function render()
    {
        /** @var Collection<int, string> $options */
        $options = match ($this->dimension) {
            'team' => Team::active()->orderBy('name')->pluck('name', 'id'),
            'queue' => app(CallQueueCache::class)->active()->pluck('name', 'id'),
            'supervisor' => Employee::where('is_active', true)
                ->where(fn ($q) => $q->where('is_manager', true)->orWhereHas('team', fn ($t) => $t->where('supervisor_id', DB::raw('employees.id'))))
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(fn ($e) => [$e->id => $e->full_name]),
        };

        $results = [];

        if (! empty($this->selectedIds)) {
            $results = $this->getComparisonData($options);
        }

        $metricLabels = [
            'occupancy' => 'Occupancy',
            'utilization' => 'Utilization',
            'productivity' => 'Productivity',
            'adherence' => 'Adherence',
            'conformance' => 'Conformance',
            'shrinkage_pct' => 'Shrinkage',
            'aht_seconds' => 'AHT',
            'asa_seconds' => 'ASA',
            'service_level' => 'Service Level',
            'fcr_pct' => 'FCR',
            'total_calls' => 'Llamadas',
        ];

        return view('operations::livewire.comparison-dashboard', [
            'options' => $options,
            'results' => $results,
            'metricLabels' => $metricLabels,
            'dimensionLabel' => match ($this->dimension) {
                'team' => 'Equipos',
                'queue' => 'Colas',
                'supervisor' => 'Supervisores',
            },
        ])->layout('layouts.app', ['title' => 'Comparativos']);
    }

    /**
     * @param  Collection<int, string>  $options
     */
    private function getComparisonData(Collection $options): array
    {
        $selected = array_map('intval', $this->selectedIds);
        $metrics = ['occupancy', 'utilization', 'productivity', 'adherence', 'conformance', 'shrinkage_pct', 'aht_seconds', 'asa_seconds', 'service_level', 'fcr_pct', 'total_calls'];

        if ($this->dimension === 'team') {
            return $this->getTeamComparison($selected, $options, $metrics);
        }

        if ($this->dimension === 'queue') {
            return $this->getQueueComparison($selected, $options, $metrics);
        }

        if ($this->dimension === 'supervisor') {
            return $this->getSupervisorComparison($selected, $options, $metrics);
        }

        return [];
    }

    /**
     * @param  array<int>  $selected
     * @param  Collection<int, string>  $options
     * @param  array<string>  $metrics
     */
    private function getTeamComparison(array $selected, Collection $options, array $metrics): array
    {
        $kpis = DailyKpi::where('granularity', 'team')
            ->whereBetween('evaluation_date', [$this->dateFrom, $this->dateTo])
            ->whereIn('dim_team_id', $selected)
            ->get();

        $agentMetrics = collect();
        if ($kpis->isEmpty()) {
            $agentMetrics = AgentDailyMetric::whereBetween('metric_date', [$this->dateFrom, $this->dateTo])
                ->whereHas('employee', fn ($e) => $e->whereIn('team_id', $selected))
                ->with('employee:id,team_id')
                ->get();
        }

        $results = [];
        foreach ($selected as $id) {
            $name = $options->get($id) ?? "#{$id}";
            $data = ['name' => $name];

            $teamKpis = $kpis->where('dim_team_id', $id);

            if ($teamKpis->isNotEmpty()) {
                foreach ($metrics as $m) {
                    $values = $teamKpis->pluck($m)->filter(fn ($v) => $v !== null);
                    $data[$m] = $values->isNotEmpty() ? round((float) $values->avg(), 1) : null;
                }
                if ($teamKpis->sum('total_calls') > 0) {
                    $data['total_calls'] = (int) $teamKpis->sum('total_calls');
                }
            } elseif ($agentMetrics->isNotEmpty()) {
                $teamAgentMetrics = $agentMetrics->filter(fn ($m) => $m->employee?->team_id === $id);
                if ($teamAgentMetrics->isNotEmpty()) {
                    $data['occupancy'] = $teamAgentMetrics->avg('efficiency_pct') ? round((float) $teamAgentMetrics->avg('efficiency_pct'), 1) : null;
                    $data['utilization'] = $teamAgentMetrics->avg('availability_pct') ? round((float) $teamAgentMetrics->avg('availability_pct'), 1) : null;
                    $data['productivity'] = $teamAgentMetrics->avg('pwi_pct') ? round((float) $teamAgentMetrics->avg('pwi_pct'), 1) : null;
                    $data['aht_seconds'] = $teamAgentMetrics->avg('weighted_aht') ? round((float) $teamAgentMetrics->avg('weighted_aht'), 1) : null;
                    $data['total_calls'] = (int) $teamAgentMetrics->sum('handled_calls');
                    foreach (['adherence', 'conformance', 'shrinkage_pct', 'asa_seconds', 'service_level', 'fcr_pct'] as $nullMetric) {
                        $data[$nullMetric] = null;
                    }
                } else {
                    foreach ($metrics as $m) {
                        $data[$m] = null;
                    }
                }
            } else {
                foreach ($metrics as $m) {
                    $data[$m] = null;
                }
            }

            $results[$id] = $data;
        }

        return $results;
    }

    /**
     * @param  array<int>  $selected
     * @param  Collection<int, string>  $options
     * @param  array<string>  $metrics
     */
    private function getQueueComparison(array $selected, Collection $options, array $metrics): array
    {
        $queueMetrics = QueueDailyMetric::whereBetween('metric_date', [$this->dateFrom, $this->dateTo])
            ->whereIn('queue_id', $selected)
            ->get();

        $results = [];
        foreach ($selected as $id) {
            $name = $options->get($id) ?? "#{$id}";
            $data = ['name' => $name];

            $items = $queueMetrics->where('queue_id', $id);

            if ($items->isNotEmpty()) {
                $totalHandled = (int) $items->sum('handled_calls');
                $totalOffered = (int) $items->sum('offered_calls');
                $totalSl = (int) $items->sum('sl_calls');
                $totalTalk = (int) $items->sum('total_talk_seconds');
                $totalWork = (int) $items->sum('total_work_seconds');
                $totalHold = (int) $items->sum('total_hold_seconds');
                $totalWait = (int) $items->sum('total_wait_seconds');
                $totalAbandon = (int) $items->sum('abandoned_calls');

                $data['total_calls'] = $totalHandled;
                $data['service_level'] = $totalHandled > 0 ? round(($totalSl / $totalHandled) * 100, 1) : null;
                $data['aht_seconds'] = $totalHandled > 0 ? round(($totalTalk + $totalWork + $totalHold) / $totalHandled, 1) : null;
                $data['asa_seconds'] = $totalHandled > 0 ? round($totalWait / $totalHandled, 1) : null;
                $data['shrinkage_pct'] = $totalOffered > 0 ? round(($totalAbandon / $totalOffered) * 100, 1) : null;

                foreach (['occupancy', 'utilization', 'productivity', 'adherence', 'conformance', 'fcr_pct'] as $m) {
                    $data[$m] = null;
                }
            } else {
                foreach ($metrics as $m) {
                    $data[$m] = null;
                }
            }

            $results[$id] = $data;
        }

        return $results;
    }

    /**
     * @param  array<int>  $selected
     * @param  Collection<int, string>  $options
     * @param  array<string>  $metrics
     */
    private function getSupervisorComparison(array $selected, Collection $options, array $metrics): array
    {
        $results = [];

        foreach ($selected as $id) {
            $name = $options->get($id) ?? "#{$id}";
            $data = ['name' => $name];

            $teamIds = Team::where('supervisor_id', $id)->pluck('id')->toArray();
            $employeeIds = Employee::where('parent_id', $id)
                ->when(! empty($teamIds), fn ($q) => $q->orWhereIn('team_id', $teamIds))
                ->pluck('id')
                ->toArray();

            $kpis = DailyKpi::whereBetween('evaluation_date', [$this->dateFrom, $this->dateTo])
                ->where(function ($q) use ($teamIds, $employeeIds) {
                    if (! empty($teamIds)) {
                        $q->where(fn ($sub) => $sub->where('granularity', 'team')->whereIn('dim_team_id', $teamIds));
                    }
                    if (! empty($employeeIds)) {
                        $q->orWhere(fn ($sub) => $sub->where('granularity', 'employee')->whereIn('dim_employee_id', $employeeIds));
                    }
                })
                ->get();

            if ($kpis->isNotEmpty()) {
                foreach ($metrics as $m) {
                    $values = $kpis->pluck($m)->filter(fn ($v) => $v !== null);
                    $data[$m] = $values->isNotEmpty() ? round((float) $values->avg(), 1) : null;
                }
                if ($kpis->sum('total_calls') > 0) {
                    $data['total_calls'] = (int) $kpis->sum('total_calls');
                }
            } elseif (! empty($employeeIds)) {
                $agentMetrics = AgentDailyMetric::whereBetween('metric_date', [$this->dateFrom, $this->dateTo])
                    ->whereIn('employee_id', $employeeIds)
                    ->get();

                if ($agentMetrics->isNotEmpty()) {
                    $data['occupancy'] = $agentMetrics->avg('efficiency_pct') ? round((float) $agentMetrics->avg('efficiency_pct'), 1) : null;
                    $data['utilization'] = $agentMetrics->avg('availability_pct') ? round((float) $agentMetrics->avg('availability_pct'), 1) : null;
                    $data['productivity'] = $agentMetrics->avg('pwi_pct') ? round((float) $agentMetrics->avg('pwi_pct'), 1) : null;
                    $data['aht_seconds'] = $agentMetrics->avg('weighted_aht') ? round((float) $agentMetrics->avg('weighted_aht'), 1) : null;
                    $data['total_calls'] = (int) $agentMetrics->sum('handled_calls');
                    foreach (['adherence', 'conformance', 'shrinkage_pct', 'asa_seconds', 'service_level', 'fcr_pct'] as $nullMetric) {
                        $data[$nullMetric] = null;
                    }
                } else {
                    foreach ($metrics as $m) {
                        $data[$m] = null;
                    }
                }
            } else {
                foreach ($metrics as $m) {
                    $data[$m] = null;
                }
            }

            $results[$id] = $data;
        }

        return $results;
    }
}
