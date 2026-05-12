<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\SaveAbsenceReasonAction;
use App\Modules\WfmModule\Livewire\Forms\AbsenceReasonForm;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use Livewire\Component;
use Livewire\WithPagination;

class ManageAbsenceReasons extends Component
{
    use WithPagination;

    public AbsenceReasonForm $form;

    public bool $showModal = false;

    public function create(): void
    {
        $this->form->resetForm();
        $this->showModal = true;
    }

    public function edit(AbsenceReasonCode $model): void
    {
        $this->form->setAbsenceReason($model);
        $this->showModal = true;
    }

    public function save(SaveAbsenceReasonAction $action): void
    {
        $this->form->validate();
        $action->execute($this->form->all(), $this->form->absenceReasonCode);
        $this->showModal = false;
        \Flux::toast('Motivo de ausencia guardado.');
    }

    public function delete(AbsenceReasonCode $model): void
    {
        $model->delete();
        \Flux::toast('Motivo eliminado.');
    }

    public function render()
    {
        return view('wfm::livewire.manage-absence-reasons', [
            'reasons' => AbsenceReasonCode::paginate(10),
        ]);
    }
}
