<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\AnalyticsModule\Models\HistoricalShrinkage;
use App\Modules\AnalyticsModule\Models\ShrinkageCategory;
use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ShrinkageDashboard extends Component
{
    use WithPagination;

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $employeeFilter = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function updated(mixed $property): void
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'employeeFilter'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $categories = ShrinkageCategory::active()->orderBy('name')->get();
        $employees = Employee::where('is_active', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        $totalsByCategory = $this->getTotalsByCategory();
        $grandTotalMinutes = $totalsByCategory->sum('total_minutes');
        $records = $this->getRecords();

        $loggedMinutes = AgentIntervalMetric::whereDate('interval_start', '>=', $this->dateFrom)
            ->whereDate('interval_start', '<=', $this->dateTo)
            ->when($this->employeeFilter, fn ($q) => $q->where('employee_id', $this->employeeFilter))
            ->sum(DB::raw('talk_seconds + hold_seconds + ready_seconds + not_ready_seconds + wrap_seconds')) / 60;

        return view('operations::livewire.shrinkage-dashboard', [
            'categories' => $categories,
            'totalsByCategory' => $totalsByCategory,
            'grandTotalMinutes' => $grandTotalMinutes,
            'loggedMinutes' => $loggedMinutes,
            'records' => $records,
            'employees' => $employees,
        ])->layout('layouts.app', ['title' => 'Shrinkage']);
    }

    private function getTotalsByCategory()
    {
        return ShrinkageCategory::active()
            ->leftJoin('historical_shrinkage', 'shrinkage_categories.id', '=', 'historical_shrinkage.shrinkage_category_id')
            ->whereDate('historical_shrinkage.date', '>=', $this->dateFrom)
            ->whereDate('historical_shrinkage.date', '<=', $this->dateTo)
            ->when($this->employeeFilter, fn ($q) => $q->where('historical_shrinkage.employee_id', $this->employeeFilter))
            ->select(
                'shrinkage_categories.id',
                'shrinkage_categories.code',
                'shrinkage_categories.name',
                'shrinkage_categories.color',
                'shrinkage_categories.is_paid',
                'shrinkage_categories.is_planned',
                DB::raw('COALESCE(SUM(historical_shrinkage.duration_minutes), 0) as total_minutes'),
                DB::raw('COUNT(DISTINCT historical_shrinkage.employee_id) as unique_employees'),
                DB::raw('COUNT(historical_shrinkage.id) as record_count'),
            )
            ->groupBy('shrinkage_categories.id', 'shrinkage_categories.code', 'shrinkage_categories.name', 'shrinkage_categories.color', 'shrinkage_categories.is_paid', 'shrinkage_categories.is_planned')
            ->orderByDesc('total_minutes')
            ->get();
    }

    private function getRecords()
    {
        return HistoricalShrinkage::with(['employee:id,first_name,last_name', 'category:id,name,code,color'])
            ->whereDate('date', '>=', $this->dateFrom)
            ->whereDate('date', '<=', $this->dateTo)
            ->when($this->employeeFilter, fn ($q) => $q->where('employee_id', $this->employeeFilter))
            ->orderByDesc('date')
            ->paginate(20);
    }
}
