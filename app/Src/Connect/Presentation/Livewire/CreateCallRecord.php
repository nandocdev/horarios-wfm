<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Livewire;

use App\Src\Connect\Application\DTOs\ManualCallRecordDTO;
use App\Src\Connect\Application\Handlers\CreateManualCallRecordHandler;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallQueue;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallRecord;
use App\Src\Connect\Infrastructure\Persistence\EloquentCaseSubtype;
use App\Src\Connect\Infrastructure\Persistence\EloquentChannel;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Nuevo Registro de Llamada')]
class CreateCallRecord extends Component
{
    public int $channel_id = 0;
    public int $queue_id = 0;
    public int $case_subtype_id = 0;
    public string $phone_number = '';
    public string $citizen_identifier = '';
    public string $description = '';

    #[Url(as: 'telefono')]
    public string $telefono = '';

    #[Url(as: 'cola')]
    public string $cola = '';

    public function mount(): void
    {
        $this->authorize('create', EloquentCallRecord::class);

        if ($this->telefono) {
            $this->phone_number = $this->telefono;
        }

        if ($this->cola) {
            $queue = EloquentCallQueue::where('name', $this->cola)
                ->orWhere('id', is_numeric($this->cola) ? $this->cola : 0)
                ->first();

            if ($queue) {
                $this->queue_id = $queue->id;
            }
        }

        if (! $this->channel_id) {
            $first = EloquentChannel::where('is_active', true)->orderBy('name')->first();
            if ($first) {
                $this->channel_id = $first->id;
                if (! $this->queue_id) {
                    $this->queue_id = EloquentCallQueue::where('is_active', true)->orderBy('name')->value('id') ?? 0;
                }
            }
        }
    }

    public function save(CreateManualCallRecordHandler $handler): void
    {
        $this->authorize('create', EloquentCallRecord::class);

        $this->validate([
            'phone_number' => ['required', 'string'],
            'queue_id' => ['required', 'integer', 'exists:call_queues,id'],
            'citizen_identifier' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $dto = new ManualCallRecordDTO(
            queueId: $this->queue_id,
            employeeId: (int) (auth()->user()?->employee?->id ?? 0),
            phoneNumber: $this->phone_number,
            citizenIdentifier: $this->citizen_identifier ?: null,
            ivrStartedAt: now()->toDateTimeString(),
            notes: $this->description ?: null,
        );

        $handler->handle($dto);

        flux()->toast('Registro de llamada creado correctamente.', variant: 'success');

        $this->reset(['phone_number', 'citizen_identifier', 'description', 'case_subtype_id']);
    }

    public function getQueuesProperty()
    {
        return EloquentCallQueue::where('is_active', true)->orderBy('name')->get();
    }

    public function getChannelsProperty()
    {
        return EloquentChannel::where('is_active', true)->orderBy('name')->get();
    }

    public function getSubtypesProperty()
    {
        return EloquentCaseSubtype::where('is_active', true)
            ->when($this->queue_id, fn ($q) => $q->where('queue_id', $this->queue_id))
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('connect::livewire.create-call-record', [
            'queues' => $this->queues,
            'channels' => $this->channels,
            'subtypes' => $this->subtypes,
        ])->layout('layouts.app');
    }
}
