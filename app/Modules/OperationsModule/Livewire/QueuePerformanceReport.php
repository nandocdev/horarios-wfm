<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Livewire\Attributes\Url;
use Livewire\Component;

class QueuePerformanceReport extends Component
{
    #[Url]
    public string $date = '';

    public function mount()
    {
        $this->date = $this->date ?: now()->toDateString();
    }

    public function render(
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
    ) {
        return view('operations::livewire.queue-performance-report', [
            'stats' => $realtimeRepo->getQueuePerformanceReport($this->date),
        ])->layout('layouts.app', ['title' => 'Performance por Cola']);
    }
}
