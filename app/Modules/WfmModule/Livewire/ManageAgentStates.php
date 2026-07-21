<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\SaveAgentStateAction;
use App\Modules\WfmModule\Livewire\Forms\AgentStateForm;
use App\Modules\WfmModule\Models\AgentState;
use App\Shared\Support\ManageCatalog;
use Livewire\Component;

class ManageAgentStates extends Component
{
    use ManageCatalog;

    public AgentStateForm $form;

    protected function catalogModel(): string
    {
        return AgentState::class;
    }

    protected function catalogLabel(): string
    {
        return 'Estado de agente';
    }

    public function save(SaveAgentStateAction $action): void
    {
        $this->form->validate();
        $action->execute($this->form->all(), $this->form->agentState);
        $this->showModal = false;
        \Flux::toast('Estado de agente guardado.');
    }

    public function render()
    {
        return view('wfm::livewire.manage-agent-states', [
            'states' => AgentState::paginate(10),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form->resetForm();
    }

    protected function loadForm(object $record): void
    {
        $this->form->setAgentState($record);
    }
}
