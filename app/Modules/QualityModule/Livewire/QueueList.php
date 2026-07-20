<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\QualityModule\Livewire\Forms\QueueFormData;
use App\Modules\QualityModule\Models\Queue;
use Livewire\Component;

class QueueList extends Component
{
    public QueueFormData $form;

    public function createQueue(): void
    {
        $this->form->validate();

        Queue::create([
            'code' => $this->form->code,
            'name' => $this->form->name,
            'description' => $this->form->description,
            'is_active' => $this->form->is_active,
        ]);

        $this->form->reset();

        $this->dispatch('queue-created');
    }

    public function toggleActive(string $queueId): void
    {
        $queue = Queue::findOrFail($queueId);
        $queue->update(['is_active' => ! $queue->is_active]);
    }

    public function render()
    {
        return view('quality::livewire.queue-list', [
            'queues' => Queue::orderBy('code')->get(),
        ]);
    }
}
