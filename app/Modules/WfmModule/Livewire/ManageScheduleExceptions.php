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

    public function render()
    {
        $exceptions = ScheduleException::query()
            ->when($this->search, function ($query) {
                $query->whereHas('employee', function ($q) {
                    $q->where('first_name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('last_name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('username', 'ilike', '%'.$this->search.'%');
                });
            })
            ->with(['employee', 'reason', 'creator'])
            ->orderBy('start_at', 'desc')
            ->paginate(15);

        return view('wfm::livewire.manage-schedule-exceptions', [
            'exceptions' => $exceptions,
            'employees' => Employee::active()->orderBy('first_name')->get(),
            'reasons' => AbsenceReasonCode::all(),
        ]);
    }
}
