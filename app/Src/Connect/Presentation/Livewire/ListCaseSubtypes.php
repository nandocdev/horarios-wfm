<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Livewire;

use App\Src\Connect\Application\DTOs\CaseSubtypeDTO;
use App\Src\Connect\Application\Handlers\CreateCaseSubtypeHandler;
use App\Src\Connect\Application\Handlers\DeleteCaseSubtypeHandler;
use App\Src\Connect\Application\Handlers\UpdateCaseSubtypeHandler;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallQueue;
use App\Src\Connect\Infrastructure\Persistence\EloquentCaseSubtype;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tipos de Consulta')]
class ListCaseSubtypes extends Component
{
    public int $queue_id = 0;
    public string $code = '';
    public string $name = '';
    public string $description = '';
    public bool $is_active = true;
    public ?EloquentCaseSubtype $editing = null;

    public function mount(): void
    {
        $this->authorize('viewAny', EloquentCaseSubtype::class);

        $this->queue_id = EloquentCallQueue::where('is_active', true)->orderBy('name')->value('id') ?? 0;
    }

    public function save(
        CreateCaseSubtypeHandler $createAction,
        UpdateCaseSubtypeHandler $updateAction,
    ): void {
        if ($this->editing) {
            $this->authorize('update', $this->editing);
        } else {
            $this->authorize('create', EloquentCaseSubtype::class);
        }

        $this->validate([
            'queue_id' => ['required', 'integer', 'exists:call_queues,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $dto = new CaseSubtypeDTO(
            name: $this->name,
            description: $this->description,
            isActive: $this->is_active,
        );

        if ($this->editing) {
            $updateAction->handle($this->editing->id, $dto);
            flux()->toast('Tipo de consulta actualizado.', variant: 'success');
        } else {
            $createAction->handle($dto);
            flux()->toast('Tipo de consulta creado.', variant: 'success');
        }

        $this->resetForm();
    }

    public function edit(int $subtypeId): void
    {
        $subtype = EloquentCaseSubtype::findOrFail($subtypeId);
        $this->authorize('update', $subtype);

        $this->editing = $subtype;
        $this->queue_id = $subtype->queue_id;
        $this->code = $subtype->code;
        $this->name = $subtype->name;
        $this->description = $subtype->description ?? '';
        $this->is_active = $subtype->is_active;
    }

    public function delete(DeleteCaseSubtypeHandler $action, int $subtypeId): void
    {
        $subtype = EloquentCaseSubtype::findOrFail($subtypeId);
        $this->authorize('delete', $subtype);

        $action->handle($subtypeId);
        $this->resetForm();
        flux()->toast('Tipo de consulta eliminado.', variant: 'success');
    }

    public function resetForm(): void
    {
        $this->queue_id = EloquentCallQueue::where('is_active', true)->orderBy('name')->value('id') ?? 0;
        $this->code = '';
        $this->name = '';
        $this->description = '';
        $this->is_active = true;
        $this->editing = null;
    }

    public function getQueuesProperty()
    {
        return EloquentCallQueue::where('is_active', true)->orderBy('name')->get();
    }

    public function getSubtypesProperty()
    {
        return EloquentCaseSubtype::with('queue')->orderBy('queue_id')->orderBy('name')->get();
    }

    public function render()
    {
        return view('connect::livewire.list-case-subtypes', [
            'queues' => $this->queues,
            'subtypes' => $this->subtypes,
        ]);
    }
}
