<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Livewire;

use App\Src\Connect\Application\DTOs\CallQueueDTO;
use App\Src\Connect\Application\Handlers\CreateCallQueueHandler;
use App\Src\Connect\Application\Handlers\DeleteCallQueueHandler;
use App\Src\Connect\Application\Handlers\UpdateCallQueueHandler;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallQueue;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Colas de Llamadas')]
class ListCallQueues extends Component
{
    public string $name = '';
    public string $description = '';
    public bool $is_active = true;
    public ?EloquentCallQueue $editing = null;

    public function mount(): void
    {
        $this->authorize('viewAny', EloquentCallQueue::class);
    }

    public function save(
        CreateCallQueueHandler $createAction,
        UpdateCallQueueHandler $updateAction,
    ): void {
        if ($this->editing) {
            $this->authorize('update', $this->editing);
        } else {
            $this->authorize('create', EloquentCallQueue::class);
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $dto = new CallQueueDTO(
            name: $this->name,
            description: $this->description,
            isActive: $this->is_active,
        );

        if ($this->editing) {
            $updateAction->handle($this->editing->id, $dto);
            flux()->toast('Cola actualizada correctamente.', variant: 'success');
        } else {
            $createAction->handle($dto);
            flux()->toast('Cola creada correctamente.', variant: 'success');
        }

        $this->resetForm();
    }

    public function edit(int $queueId): void
    {
        $queue = EloquentCallQueue::findOrFail($queueId);
        $this->authorize('update', $queue);

        $this->editing = $queue;
        $this->name = $queue->name;
        $this->description = $queue->description ?? '';
        $this->is_active = $queue->is_active;
    }

    public function delete(DeleteCallQueueHandler $action, int $queueId): void
    {
        $queue = EloquentCallQueue::findOrFail($queueId);
        $this->authorize('delete', $queue);

        $action->handle($queueId);
        $this->resetForm();
        flux()->toast('Cola eliminada correctamente.', variant: 'success');
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->description = '';
        $this->is_active = true;
        $this->editing = null;
    }

    public function getQueuesProperty()
    {
        return EloquentCallQueue::orderBy('name')->get();
    }

    public function render()
    {
        return view('connect::livewire.list-call-queues', [
            'queues' => $this->queues,
        ]);
    }
}
