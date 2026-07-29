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
use App\Shared\Support\CallQueueCache;
use App\Shared\Support\ManageCatalog;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ListCaseSubtypes extends Component
{
    use ManageCatalog;

    public CaseSubtypeForm $form;

    public ?CaseSubtype $editing = null;

    protected function catalogModel(): string
    {
        return CaseSubtype::class;
    }

    protected function catalogLabel(): string
    {
        return 'Tipo de consulta';
    }

    public function create(): void
    {
        $this->editing = null;
        $this->form->queue_id = CallQueue::active()->orderBy('name')->value('id') ?? 0;
        $this->form->code = '';
        $this->form->name = '';
        $this->form->description = null;
        $this->form->is_active = true;
    }

    public function edit(int $id): void
    {
        $subtype = CaseSubtype::findOrFail($id);
        Gate::authorize('update', $subtype);
        $this->editing = $subtype;
        $this->form->queue_id = $subtype->queue_id;
        $this->form->code = $subtype->code;
        $this->form->name = $subtype->name;
        $this->form->description = $subtype->description;
        $this->form->is_active = $subtype->is_active;
    }

    public function save(CreateCaseSubtypeAction $createAction, UpdateCaseSubtypeAction $updateAction): void
    {
        if ($this->editing) {
            Gate::authorize('update', $this->editing);
        } else {
            Gate::authorize('create', CaseSubtype::class);
        }

        $this->form->validate();

        $activeIds = app(CallQueueCache::class)->active()->pluck('id')->toArray();
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

    protected function performDelete(object $record): void
    {
        $action = app(DeleteCaseSubtypeAction::class);
        $action->execute($record);
        session()->flash('success', 'Tipo de consulta eliminado correctamente.');
    }

    protected function resetForm(): void
    {
        $this->form->queue_id = CallQueue::active()->orderBy('name')->value('id') ?? 0;
        $this->form->code = '';
        $this->form->name = '';
        $this->form->description = null;
        $this->form->is_active = true;
        $this->editing = null;
    }

    protected function loadForm(object $record): void
    {
        // Inline form — loadForm is handled by edit()
    }

    public function render(): mixed
    {
        return view('connect::livewire.list-case-subtypes', [
            'queues' => app(CallQueueCache::class)->active(),
            'subtypes' => CaseSubtype::with('queue')
                ->when($this->search, fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"))
                ->orderBy('queue_id')
                ->orderBy('name')
                ->paginate(10),
        ]);
    }
}
