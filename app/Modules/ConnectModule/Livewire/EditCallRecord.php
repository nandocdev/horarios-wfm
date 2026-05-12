<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Actions\CompleteCallRecordAction;
use App\Modules\ConnectModule\DTOs\CallCompleteDTO;
use App\Modules\ConnectModule\Livewire\Forms\CompleteCallRecordForm;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Models\CaseSubtype;
use App\Modules\ConnectModule\Models\Channel;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class EditCallRecord extends Component
{
    public CompleteCallRecordForm $form;

    public CallRecord $callRecord;

    public function mount(CallRecord $callRecord): void
    {
        Gate::authorize('update', $callRecord);

        $this->callRecord = $callRecord;
        $this->form = new CompleteCallRecordForm($this, 'form');
        $this->form->citizen_identifier = $callRecord->citizen_identifier ?? '';
        $this->form->case_subtype_id = $callRecord->case_subtype_id ?? 0;
        // infer channel and queue id from queue_id stored on the record
        $queue = $callRecord->queue;
        $this->form->channel_id = $queue?->channel_id ?? 0;
        $this->form->queue_id = $queue?->id ?? 0;
        $this->form->description = $callRecord->description ?? '';
    }

    public function save(CompleteCallRecordAction $action): void
    {
        $this->form->validate();

        $dto = CallCompleteDTO::fromForm(array_merge($this->form->toArray(), [
            'employee_id' => auth()->user()?->employee?->id,
        ]));

        $action->execute($this->callRecord, $dto);

        session()->flash('success', 'Registro actualizado correctamente.');
        $this->redirect(route('contact-center.calls.index'), navigate: true);
    }

    public function updatedFormChannelId($value): void
    {
        if (empty($value)) {
            $this->form->queue_id = CallQueue::where('is_active', true)->orderBy('name')->value('id') ?? 0;
        } else {
            $firstQueueId = CallQueue::where('channel_id', $value)->where('is_active', true)->orderBy('name')->value('id');
            $this->form->queue_id = $firstQueueId ?? CallQueue::where('is_active', true)->orderBy('name')->value('id') ?? 0;
        }

        $this->form->case_subtype_id = 0;
    }

    public function updatedFormQueueId($value): void
    {
        $this->form->case_subtype_id = 0;
    }

    public function render(): mixed
    {
        $recordQueueId = $this->callRecord->queue_id;
        $subtypes = CaseSubtype::byQueue($this->form->queue_id ?: $recordQueueId)->orderBy('name')->get();
        $channels = Channel::where('is_active', true)->orderBy('name')->get();
        $queues = CallQueue::when($this->form->channel_id, fn ($q) => $q->where('channel_id', $this->form->channel_id))->where('is_active', true)->orderBy('name')->get();

        return view('connect::livewire.edit-call-record', [
            'subtypes' => $subtypes,
            'channels' => $channels,
            'queues' => $queues,
        ]);
    }
}
