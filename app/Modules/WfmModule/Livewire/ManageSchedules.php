<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\SaveScheduleAction;
use App\Modules\WfmModule\Livewire\Forms\ScheduleForm;
use App\Modules\WfmModule\Models\Schedule;
use Livewire\Component;
use Livewire\WithPagination;

class ManageSchedules extends Component
{
    use WithPagination;

    public ScheduleForm $form;

    public bool $showModal = false;

    public function create(): void
    {
        $this->form->resetForm();
        $this->showModal = true;
    }

    public function edit(Schedule $schedule): void
    {
        $this->form->setSchedule($schedule);
        $this->showModal = true;
    }

    public function save(SaveScheduleAction $action): void
    {
        $this->form->validate();

        $action->execute($this->form->all(), $this->form->schedule);

        $this->showModal = false;
        \Flux::toast('Turno guardado exitosamente.');
    }

    public function delete(Schedule $schedule): void
    {
        $schedule->delete();
        \Flux::toast('Turno eliminado.');
    }

    public function updated($property): void
    {
        if (in_array($property, ['form.start_time', 'form.end_time'])) {
            $this->form->calculateTotalMinutes();
        }
    }

    public function render()
    {
        return view('wfm::livewire.manage-schedules', [
            'schedules' => Schedule::paginate(10),
        ]);
    }
}
