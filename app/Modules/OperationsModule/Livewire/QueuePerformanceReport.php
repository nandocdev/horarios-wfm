<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\ConnectModule\Models\CallQueue;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Livewire\Attributes\Url;
use Livewire\Component;

class QueuePerformanceReport extends Component
{
    #[Url]
    public string $date = '';

    #[Url]
    public ?int $queueId = null;

    public function mount(): void
    {
        $this->date = $this->date ?: now()->toDateString();
    }

    public function render(
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
    ) {
        $queues = CallQueue::active()->orderBy('name')->get();
        $selectedQueue = $this->queueId ? $queues->firstWhere('id', $this->queueId) : null;

        $stats = $realtimeRepo->getQueuePerformanceReport($this->date);

        if ($selectedQueue) {
            $stats = $stats->filter(fn ($s) => $s->queue_name === $selectedQueue->name)->values();
        }

        $realtimeStats = $realtimeRepo->getCsqRealtimeStats();

        return view('operations::livewire.queue-performance-report', [
            'queues' => $queues,
            'selectedQueue' => $selectedQueue,
            'stats' => $stats,
            'realtimeStats' => $realtimeStats,
            'totalOffered' => $stats->sum('total_offered'),
            'totalHandled' => $stats->sum('handled'),
            'totalAbandoned' => $stats->sum('abandoned'),
        ])->layout('layouts.app', ['title' => $selectedQueue ? "Dashboard: {$selectedQueue->name}" : 'Dashboard de Colas']);
    }
}
