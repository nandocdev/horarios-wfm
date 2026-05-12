<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\SaveScheduledActivityAction;
use App\Modules\WfmModule\Livewire\Forms\ScheduledActivityForm;
use App\Modules\WfmModule\Models\ActivityType;
use App\Modules\WfmModule\Models\ScheduledActivityDefinition;
use Livewire\Component;
use Livewire\WithPagination;

class ManageScheduledActivities extends Component
{
    use WithPagination;

    public ScheduledActivityForm $form;

    public bool $showModal = false;

    public function create(): void
    {
        $this->form->resetForm();
        $this->showModal = true;
    }

    public function edit(ScheduledActivityDefinition $model): void
    {
        $this->form->setDefinition($model);
        $this->showModal = true;
    }

    public function save(SaveScheduledActivityAction $action): void
    {
        $this->form->validate();
        $action->execute($this->form->all(), $this->form->definition);
        $this->showModal = false;
        \Flux::toast('Definición de actividad guardada.');
    }

    public function delete(ScheduledActivityDefinition $model): void
    {
        $model->delete();
        \Flux::toast('Definición eliminada.');
    }

    public function render()
    {
        return view('wfm::livewire.manage-scheduled-activities', [
            'definitions' => ScheduledActivityDefinition::with('activityType')->paginate(10),
            'activityTypes' => ActivityType::all(),
        ]);
    }
}
