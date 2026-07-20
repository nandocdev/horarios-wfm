<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Models\Channel;
use App\Modules\CoreModule\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

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
        Gate::authorize('viewAny', CallRecord::class);

        $this->dateFrom = Carbon::today()->toDateString();
        $this->dateTo = Carbon::today()->toDateString();

        $employee = auth()->user()?->employee;
        if ($employee) {
            $this->employeeFilter = (string) $employee->id;
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'dateFrom', 'dateTo', 'employeeFilter'])) {
            $this->resetPage();
        }
    }

    public function getFilteredRecordsProperty()
    {
        $user = auth()->user();

        return CallRecord::with(['employee', 'caseSubtype.queue', 'queue'])
            ->when($this->employeeFilter, fn ($query) => $query->where('employee_id', $this->employeeFilter))
            ->when(! $this->employeeFilter && ! $user?->can('viewAny', CallRecord::class), fn ($query) => $query->where('employee_id', $user?->employee?->id ?? 0))
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('phone_number', 'ilike', "%{$this->search}%")
                    ->orWhere('citizen_identifier', 'ilike', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn ($query) => $query->whereDate('ivr_started_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('ivr_started_at', '<=', $this->dateTo))
            ->orderByDesc('ivr_started_at')
            ->paginate($this->perPage);
    }

    public function getQueuesProperty(): mixed
    {
        return CallQueue::active()->orderBy('name')->get();
    }

    public function getChannelsProperty(): mixed
    {
        return Channel::where('is_active', true)->orderBy('name')->get();
    }

    public function getEmployeesProperty(): mixed
    {
        return User::whereHas('employee')->with('employee')->orderBy('name')->get();
    }

    public function render(): mixed
    {
        return view('connect::livewire.list-call-records', [
            'records' => $this->filteredRecords,
            'queues' => $this->queues,
            'channels' => $this->channels,
            'employees' => $this->employees,
        ]);
    }
}
