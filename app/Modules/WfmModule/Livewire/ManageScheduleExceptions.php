<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Livewire\Forms\ExceptionForm;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\ScheduleException;
use Livewire\Component;
use Livewire\WithPagination;

class ManageScheduleExceptions extends Component
{
    use WithPagination;

    public ExceptionForm $form;

    public bool $showCreateModal = false;

    public ?int $selectedExceptionId = null;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $reasonFilter = '';

    /** @var string[] */
    public array $statusFilter = ['active', 'pending'];

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->endOfMonth()->toDateString();
    }

    public function create(): void
    {
        $this->form->reset();
        $this->selectedExceptionId = null;
        $this->showCreateModal = true;
    }

    public function edit(int $id): void
    {
        $exception = ScheduleException::findOrFail($id);
        $this->selectedExceptionId = $id;
        $this->form->setException($exception);
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $this->form->validate();

        if ($this->selectedExceptionId) {
            $exception = ScheduleException::findOrFail($this->selectedExceptionId);
            $exception->update($this->form->toArray());
            \Flux::toast(__('Excepción actualizada exitosamente.'));
        } else {
            ScheduleException::create($this->form->toArray());
            \Flux::toast(__('Excepción registrada exitosamente.'));
        }

        $this->showCreateModal = false;
    }

    public function delete(int $id): void
    {
        ScheduleException::destroy($id);
        \Flux::toast(__('Excepción eliminada.'));
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $now = now();

        $exceptions = ScheduleException::query()
            ->with(['employee', 'reason', 'creator'])
            ->when($this->search, function ($query) {
                $query->whereHas('employee', function ($q) {
                    $q->where('first_name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('last_name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('username', 'ilike', '%'.$this->search.'%');
                });
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('end_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('start_at', '<=', $this->dateTo))
            ->when($this->reasonFilter, fn ($q) => $q->where('absence_reason_code_id', $this->reasonFilter))
            ->when(count($this->statusFilter) < 3, function ($query) use ($now) {
                $query->where(function ($q) use ($now) {
                    if (in_array('active', $this->statusFilter)) {
                        $q->orWhere(function ($sq) use ($now) {
                            $sq->whereDate('start_at', '<=', $now->toDateString())
                                ->whereDate('end_at', '>=', $now->toDateString());
                        });
                    }
                    if (in_array('pending', $this->statusFilter)) {
                        $q->orWhereDate('start_at', '>', $now->toDateString());
                    }
                    if (in_array('completed', $this->statusFilter)) {
                        $q->orWhereDate('end_at', '<', $now->toDateString());
                    }
                });
            })
            ->orderBy('start_at', 'desc')
            ->paginate(15);

        return view('wfm::livewire.manage-schedule-exceptions', [
            'exceptions' => $exceptions,
            'employees' => Employee::active()->orderBy('first_name')->get(),
            'reasons' => AbsenceReasonCode::all(),
            'now' => $now,
        ]);
    }
}
