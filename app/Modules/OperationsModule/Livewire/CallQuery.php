<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Models\Channel;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Support\CallQueueCache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CallQuery extends Component
{
    use WithPagination;

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $queueFilter = '';

    public string $channelFilter = '';

    public string $employeeFilter = '';

    public string $dispositionFilter = '';

    public string $statusFilter = '';

    public string $search = '';

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function updated(mixed $property): void
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'queueFilter', 'channelFilter', 'employeeFilter', 'dispositionFilter', 'statusFilter', 'search'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $records = $this->getRecords();
        $totals = $this->getTotals();

        return view('operations::livewire.call-query', [
            'records' => $records,
            'totals' => $totals,
            'queues' => app(CallQueueCache::class)->active(),
            'channels' => Channel::where('is_active', true)->orderBy('name')->get(),
            'employees' => Employee::where('is_active', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ])->layout('layouts.app', ['title' => 'Consulta de Llamadas']);
    }

    private function getRecords()
    {
        return CallRecord::with(['queue:id,name', 'employee:id,first_name,last_name'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('ivr_started_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('ivr_started_at', '<=', $this->dateTo))
            ->when($this->queueFilter, fn ($q) => $q->whereHas('queue', fn ($qq) => $qq->where('name', $this->queueFilter)))
            ->when($this->employeeFilter, fn ($q) => $q->where('employee_id', $this->employeeFilter))
            ->when($this->dispositionFilter !== '', fn ($q) => $q->where('contact_disposition', (int) $this->dispositionFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('phone_number', 'ilike', "%{$this->search}%")
                    ->orWhere('citizen_identifier', 'ilike', "%{$this->search}%");
            }))
            ->orderByDesc('ivr_started_at')
            ->paginate(20);
    }

    private function getTotals(): object
    {
        $query = CallRecord::whereNotNull('queue_id')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('ivr_started_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('ivr_started_at', '<=', $this->dateTo))
            ->when($this->queueFilter, fn ($q) => $q->whereHas('queue', fn ($qq) => $qq->where('name', $this->queueFilter)))
            ->when($this->employeeFilter, fn ($q) => $q->where('employee_id', $this->employeeFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter));

        return $query->select(
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN contact_disposition = 2 THEN 1 ELSE 0 END) as handled'),
            DB::raw('SUM(CASE WHEN contact_disposition = 1 THEN 1 ELSE 0 END) as abandoned'),
            DB::raw('AVG(talk_time) as avg_talk'),
            DB::raw('AVG(talk_time + work_time) as avg_aht'),
            DB::raw('AVG(queue_time) as avg_asa'),
        )->first() ?? (object) ['total' => 0, 'handled' => 0, 'abandoned' => 0, 'avg_talk' => null, 'avg_aht' => null, 'avg_asa' => null];
    }
}
