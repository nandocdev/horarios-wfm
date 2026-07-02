<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Livewire;

use App\Src\Connect\Infrastructure\Persistence\EloquentCallQueue;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallRecord;
use App\Src\Connect\Infrastructure\Persistence\EloquentCaseSubtype;
use App\Src\Connect\Infrastructure\Persistence\EloquentChannel;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Editar Registro de Llamada')]
class EditCallRecord extends Component
{
    public EloquentCallRecord $callRecord;
    public int $channel_id = 0;
    public int $queue_id = 0;
    public int $case_subtype_id = 0;
    public string $citizen_identifier = '';
    public string $description = '';

    public function mount(EloquentCallRecord $callRecord): void
    {
        $this->authorize('update', $callRecord);

        $this->callRecord = $callRecord;
        $this->citizen_identifier = $callRecord->citizen_identifier ?? '';
        $this->case_subtype_id = $callRecord->case_subtype_id ?? 0;
        $this->queue_id = $callRecord->queue_id ?? 0;
        $this->description = $callRecord->description ?? '';
    }

    public function save(): void
    {
        $this->authorize('update', $this->callRecord);

        $this->validate([
            'citizen_identifier' => ['nullable', 'string'],
            'case_subtype_id' => ['nullable', 'integer', 'exists:case_subtypes,id'],
            'description' => ['nullable', 'string'],
        ]);

        $this->callRecord->update([
            'citizen_identifier' => $this->citizen_identifier,
            'case_subtype_id' => $this->case_subtype_id ?: null,
            'description' => $this->description,
        ]);

        flux()->toast('Registro actualizado correctamente.', variant: 'success');

        $this->redirectRoute('connect.call-records', navigate: true);
    }

    public function render()
    {
        $channels = EloquentChannel::where('is_active', true)->orderBy('name')->get();
        $queues = EloquentCallQueue::where('is_active', true)->orderBy('name')->get();
        $subtypes = EloquentCaseSubtype::where('is_active', true)
            ->when($this->queue_id, fn ($q) => $q->where('queue_id', $this->queue_id))
            ->orderBy('name')
            ->get();

        return view('connect::livewire.edit-call-record', [
            'channels' => $channels,
            'queues' => $queues,
            'subtypes' => $subtypes,
        ]);
    }
}
