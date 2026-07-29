<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Actions\CreateCallQueueAction;
use App\Modules\ConnectModule\Actions\DeleteCallQueueAction;
use App\Modules\ConnectModule\Actions\UpdateCallQueueAction;
use App\Modules\ConnectModule\DTOs\CallQueueDTO;
use App\Modules\ConnectModule\Livewire\Forms\CallQueueForm;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Shared\Support\ManageCatalog;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ListCallQueues extends Component
{
    use ManageCatalog;

    public CallQueueForm $form;

    public ?CallQueue $editing = null;

    protected function catalogModel(): string
    {
        return CallQueue::class;
    }

    protected function catalogLabel(): string
    {
        return 'Cola';
    }

    public function mount(): void
    {
        Gate::authorize('viewAny', CallQueue::class);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editing = null;
    }

    public function edit(int $id): void
    {
        $queue = CallQueue::findOrFail($id);
        Gate::authorize('update', $queue);
        $this->editing = $queue;
        $this->form->name = $queue->name;
        $this->form->description = $queue->description;
        $this->form->is_active = $queue->is_active;
    }

    public function save(CreateCallQueueAction $createAction, UpdateCallQueueAction $updateAction): void
    {
        if ($this->editing) {
            Gate::authorize('update', $this->editing);
        } else {
            Gate::authorize('create', CallQueue::class);
        }

        $this->form->validate();

        $dto = CallQueueDTO::fromForm($this->form->toArray());

        if ($this->editing) {
            $updateAction->execute($this->editing, $dto);
            session()->flash('success', 'Cola actualizada correctamente.');
        } else {
            $createAction->execute($dto);
            session()->flash('success', 'Cola creada correctamente.');
        }

        $this->editing = null;
        $this->resetForm();
    }

    protected function performDelete(object $record): void
    {
        $action = app(DeleteCallQueueAction::class);
        $action->execute($record);
        session()->flash('success', 'Cola eliminada correctamente.');
    }

    public function resetForm(): void
    {
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
        return view('connect::livewire.list-call-queues', [
            'queues' => CallQueue::withCount('subtypes')
                ->when($this->search, fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(10),
        ]);
    }
}
