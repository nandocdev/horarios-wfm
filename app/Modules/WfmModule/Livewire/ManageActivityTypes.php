<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\SaveActivityTypeAction;
use App\Modules\WfmModule\Livewire\Forms\ActivityTypeForm;
use App\Modules\WfmModule\Models\ActivityType;
use Livewire\Component;
use Livewire\WithPagination;

class ManageActivityTypes extends Component
{
    use WithPagination;

    public ActivityTypeForm $form;

    public bool $showModal = false;

    public function create(): void
    {
        $this->form->resetForm();
        $this->showModal = true;
    }

    public function edit(ActivityType $activityType): void
    {
        $this->form->setActivityType($activityType);
        $this->showModal = true;
    }

    public function save(SaveActivityTypeAction $action): void
    {
        $this->form->validate();
        $action->execute($this->form->all(), $this->form->activityType);
        $this->showModal = false;
        \Flux::toast('Tipo de actividad guardado.');
    }

    public function delete(ActivityType $activityType): void
    {
        $activityType->delete();
        \Flux::toast('Tipo de actividad eliminado.');
    }

    public function render()
    {
        return view('wfm::livewire.manage-activity-types', [
            'activityTypes' => ActivityType::paginate(10),
        ]);
    }
}
