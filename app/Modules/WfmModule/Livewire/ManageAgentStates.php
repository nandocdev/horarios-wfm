<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\SaveAgentStateAction;
use App\Modules\WfmModule\Livewire\Forms\AgentStateForm;
use App\Modules\WfmModule\Models\AgentState;
use Livewire\Component;
use Livewire\WithPagination;

class ManageAgentStates extends Component
{
    use WithPagination;

    public AgentStateForm $form;

    public bool $showModal = false;

    public function create(): void
    {
        $this->form->resetForm();
        $this->showModal = true;
    }

    public function edit(AgentState $model): void
    {
        $this->form->setAgentState($model);
        $this->showModal = true;
    }

    public function save(SaveAgentStateAction $action): void
    {
        $this->form->validate();
        $action->execute($this->form->all(), $this->form->agentState);
        $this->showModal = false;
        \Flux::toast('Estado de agente guardado.');
    }

    public function delete(AgentState $model): void
    {
        $model->delete();
        \Flux::toast('Estado eliminado.');
    }

    public function render()
    {
        return view('wfm::livewire.manage-agent-states', [
            'states' => AgentState::paginate(10),
        ]);
    }
}
