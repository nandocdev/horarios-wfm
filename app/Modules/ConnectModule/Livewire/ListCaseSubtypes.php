<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Actions\CreateCaseSubtypeAction;
use App\Modules\ConnectModule\Actions\DeleteCaseSubtypeAction;
use App\Modules\ConnectModule\Actions\UpdateCaseSubtypeAction;
use App\Modules\ConnectModule\DTOs\CaseSubtypeDTO;
use App\Modules\ConnectModule\Livewire\Forms\CaseSubtypeForm;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CaseSubtype;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ListCaseSubtypes extends Component
{
    public CaseSubtypeForm $form;

    public ?CaseSubtype $editing = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', CaseSubtype::class);

        $this->form = new CaseSubtypeForm($this, 'form');
        $this->resetForm();
    }

    public function save(CreateCaseSubtypeAction $createAction, UpdateCaseSubtypeAction $updateAction): void
    {
        if ($this->editing) {
            Gate::authorize('update', $this->editing);
        } else {
            Gate::authorize('create', CaseSubtype::class);
        }

        $this->form->validate();

        $activeIds = CallQueue::active()->pluck('id')->toArray();
        if (! in_array($this->form->queue_id, $activeIds, true)) {
            $this->addError('form.queue_id', 'La cola seleccionada no es válida.');

            return;
        }

        $dto = CaseSubtypeDTO::fromForm($this->form->toArray());

        if ($this->editing) {
            $updateAction->execute($this->editing, $dto);
            session()->flash('success', 'Tipo de consulta actualizado correctamente.');
        } else {
            $createAction->execute($dto);
            session()->flash('success', 'Tipo de consulta creado correctamente.');
        }

        $this->editing = null;
        $this->resetForm();
    }

    public function edit(int $subtypeId): void
    {
        $subtype = CaseSubtype::findOrFail($subtypeId);
        Gate::authorize('update', $subtype);

        $this->editing = $subtype;
        $this->form->queue_id = $subtype->queue_id;
        $this->form->code = $subtype->code;
        $this->form->name = $subtype->name;
        $this->form->description = $subtype->description;
        $this->form->is_active = $subtype->is_active;
    }

    public function delete(DeleteCaseSubtypeAction $action, int $subtypeId): void
    {
        $subtype = CaseSubtype::findOrFail($subtypeId);
        Gate::authorize('delete', $subtype);

        $action->execute($subtype);
        $this->resetForm();
        session()->flash('success', 'Tipo de consulta eliminado correctamente.');
    }

    public function resetForm(): void
    {
        $this->form->queue_id = CallQueue::active()->orderBy('name')->value('id') ?? 0;
        $this->form->code = '';
        $this->form->name = '';
        $this->form->description = null;
        $this->form->is_active = true;
        $this->editing = null;
    }

    public function getQueuesProperty()
    {
        return CallQueue::active()->orderBy('name')->get();
    }

    public function getSubtypesProperty()
    {
        return CaseSubtype::with('queue')->orderBy('queue_id')->orderBy('name')->get();
    }

    public function render(): mixed
    {
        return view('connect::livewire.list-case-subtypes', [
            'queues' => $this->queues,
            'subtypes' => $this->subtypes,
        ]);
    }
}
