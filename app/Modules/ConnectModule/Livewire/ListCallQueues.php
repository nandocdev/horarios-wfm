<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Actions\CreateCallQueueAction;
use App\Modules\ConnectModule\Actions\DeleteCallQueueAction;
use App\Modules\ConnectModule\Actions\UpdateCallQueueAction;
use App\Modules\ConnectModule\DTOs\CallQueueDTO;
use App\Modules\ConnectModule\Livewire\Forms\CallQueueForm;
use App\Modules\ConnectModule\Models\CallQueue;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ListCallQueues extends Component
{
    public CallQueueForm $form;

    public ?CallQueue $editing = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', CallQueue::class);

        $this->form = new CallQueueForm($this, 'form');
        $this->resetForm();
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

    public function edit(int $queueId): void
    {
        $queue = CallQueue::findOrFail($queueId);
        Gate::authorize('update', $queue);

        $this->editing = $queue;
        $this->form->name = $queue->name;
        $this->form->description = $queue->description;
        $this->form->is_active = $queue->is_active;
    }

    public function delete(DeleteCallQueueAction $action, int $queueId): void
    {
        $queue = CallQueue::findOrFail($queueId);
        Gate::authorize('delete', $queue);

        $action->execute($queue);
        $this->resetForm();
        session()->flash('success', 'Cola eliminada correctamente.');
    }

    public function resetForm(): void
    {
        $this->form->name = '';
        $this->form->description = null;
        $this->form->is_active = true;
        $this->editing = null;
    }

    public function getQueuesProperty()
    {
        return CallQueue::withCount('subtypes')->orderBy('name')->get();
    }

    public function render(): mixed
    {
        return view('connect::livewire.list-call-queues', [
            'queues' => $this->queues,
        ]);
    }
}
