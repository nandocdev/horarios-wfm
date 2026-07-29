<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\AnalyticsModule\Models\DailyKpi;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\OrganizationModule\Models\Team;
use App\Modules\PersonnelModule\Models\Employee;
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

    public function toggleId(int $id): void
    {
        if (in_array($id, $this->selectedIds)) {
            $this->selectedIds = array_values(array_filter($this->selectedIds, fn ($i) => $i !== $id));
        } else {
            $this->selectedIds[] = $id;
        }
    }

    public function render()
    {
        $options = match ($this->dimension) {
            'team' => Team::active()->orderBy('name')->get()->mapWithKeys(fn ($t) => [$t->id => $t->name]),
            'queue' => CallQueue::active()->orderBy('name')->get()->mapWithKeys(fn ($q) => [$q->id => $q->name]),
            'supervisor' => Employee::where('is_active', true)
                ->where(fn ($q) => $q->where('is_manager', true)->orWhereHas('team', fn ($t) => $t->where('supervisor_id', DB::raw('employees.id'))))
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(fn ($e) => [$e->id => $e->full_name]),
        };

        $results = collect();

        if (! empty($this->selectedIds)) {
            $results = $this->getComparisonData();
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

    private function getComparisonData(): array
    {
        $granularity = match ($this->dimension) {
            'team' => 'team',
            'supervisor' => 'employee',
            'queue' => 'employee',
        };

        $kpis = DailyKpi::where('granularity', $granularity)
            ->where('evaluation_date', '>=', $this->dateFrom)
            ->where('evaluation_date', '<=', $this->dateTo)
            ->whereIn($this->dimension === 'supervisor' ? 'dim_employee_id' : 'dim_team_id', $this->selectedIds)
            ->get();

        $metrics = ['occupancy', 'utilization', 'productivity', 'adherence', 'conformance', 'shrinkage_pct', 'aht_seconds', 'asa_seconds', 'service_level', 'fcr_pct', 'total_calls'];

        return collect($this->selectedIds)->mapWithKeys(function ($id) use ($kpis, $metrics) {
            $filterField = $this->dimension === 'supervisor' ? 'dim_employee_id' : 'dim_team_id';
            $items = $kpis->where($filterField, $id);

            $name = $this->options->get($id) ?? "#{$id}";

            $data = ['name' => $name];
            foreach ($metrics as $m) {
                $values = $items->pluck($m)->filter(fn ($v) => $v !== null);
                $data[$m] = $values->isNotEmpty() ? round($values->avg(), 1) : null;
            }

            return [$id => $data];
        })->toArray();
    }
}
