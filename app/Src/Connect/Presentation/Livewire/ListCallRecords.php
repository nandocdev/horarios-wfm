<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Livewire;

use App\Src\Connect\Infrastructure\Persistence\EloquentCallQueue;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallRecord;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Registros de Llamadas')]
class ListCallRecords extends Component
{
    use WithPagination;

    public int $perPage = 15;
    public string $search = '';
    public string $statusFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $employeeFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', EloquentCallRecord::class);

        $this->dateFrom = Carbon::today()->toDateString();
        $this->dateTo = Carbon::today()->toDateString();

        $employee = auth()->user()?->employee;
        if ($employee) {
            $this->employeeFilter = (string) $employee->id;
        }
    }

    public function getFilteredRecordsProperty()
    {
        return EloquentCallRecord::with(['employee', 'caseSubtype', 'queue'])
            ->when($this->employeeFilter, fn ($q) => $q->where('employee_id', $this->employeeFilter))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('phone_number', 'like', "%{$this->search}%")
                    ->orWhere('citizen_identifier', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('ivr_started_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('ivr_started_at', '<=', $this->dateTo))
            ->orderByDesc('ivr_started_at')
            ->paginate($this->perPage);
    }

    public function getQueuesProperty()
    {
        return EloquentCallQueue::where('is_active', true)->orderBy('name')->get();
    }

    public function render()
    {
        return view('connect::livewire.list-call-records', [
            'records' => $this->filteredRecords,
            'queues' => $this->queues,
        ]);
    }
}
