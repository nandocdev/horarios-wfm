<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\SaveAbsenceReasonAction;
use App\Modules\WfmModule\Livewire\Forms\AbsenceReasonForm;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Shared\Support\ManageCatalog;
use Livewire\Component;

class ManageAbsenceReasons extends Component
{
    use ManageCatalog;

    public AbsenceReasonForm $form;

    protected function catalogModel(): string
    {
        return AbsenceReasonCode::class;
    }

    protected function catalogLabel(): string
    {
        return 'Motivo de ausencia';
    }

    public function save(SaveAbsenceReasonAction $action): void
    {
        $this->form->validate();
        $action->execute($this->form->all(), $this->form->absenceReasonCode);
        $this->showModal = false;
        \Flux::toast('Motivo de ausencia guardado.');
    }

    public function render()
    {
        return view('wfm::livewire.manage-absence-reasons', [
            'reasons' => AbsenceReasonCode::paginate(10),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form->resetForm();
    }

    protected function loadForm(object $record): void
    {
        $this->form->setAbsenceReason($record);
    }
}
