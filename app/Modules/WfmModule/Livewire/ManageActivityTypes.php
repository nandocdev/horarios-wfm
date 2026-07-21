<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\SaveActivityTypeAction;
use App\Modules\WfmModule\Livewire\Forms\ActivityTypeForm;
use App\Modules\WfmModule\Models\ActivityType;
use App\Shared\Support\ManageCatalog;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class ManageActivityTypes extends Component
{
    use ManageCatalog;

    public ActivityTypeForm $form;

    protected function catalogModel(): string
    {
        return ActivityType::class;
    }

    protected function catalogLabel(): string
    {
        return 'Tipo de actividad';
    }

    public function save(SaveActivityTypeAction $action): void
    {
        $this->form->validate();
        $action->execute($this->form->all(), $this->form->activityType);
        $this->showModal = false;
        \Flux::toast('Tipo de actividad guardado.');
    }

    public function render()
    {
        return view('wfm::livewire.manage-activity-types', [
            'activityTypes' => ActivityType::paginate(10),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form->resetForm();
    }

    protected function loadForm(Model $record): void
    {
        $this->form->setActivityType($record);
    }
}
