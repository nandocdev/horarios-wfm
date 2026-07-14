<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\QualityModule\Models\Queue;
use Livewire\Component;

class QueueList extends Component
{
    public string $code = '';

    public string $name = '';

    public function createQueue(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'max:20', 'unique:quality_queues,code'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        Queue::create([
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => true,
        ]);

        $this->reset(['code', 'name']);

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
