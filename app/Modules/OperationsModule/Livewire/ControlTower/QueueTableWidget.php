<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\ConnectModule\Enums\ContactDisposition;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class QueueTableWidget extends Component
{
    public array $employeeIds = [];

    public string $selectedDate;

    public function placeholder()
    {
        return '<div class="h-48 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function render()
    {
        $today = $this->selectedDate;

        $queues = CallQueue::where('is_active', true)
            ->get()
            ->map(function ($queue) use ($today) {
                $handled = CallRecord::where('queue_id', $queue->id)
                    ->whereDate('ivr_started_at', $today)
                    ->where('contact_disposition', ContactDisposition::Handled->value);

                $totalHandled = (int) (clone $handled)->count();
                $totalCalls = (int) CallRecord::where('queue_id', $queue->id)
                    ->whereDate('ivr_started_at', $today)->count();

                $waiting = max(0, $totalCalls - $totalHandled);
                $slaCalls = (int) (clone $handled)->where('queue_time', '<=', 20)->count();
                $slaPct = $totalHandled > 0 ? round(($slaCalls / $totalHandled) * 100, 1) : 0;

                return [
                    'name' => $queue->name,
                    'sla' => $slaPct,
                    'slaClass' => $slaPct >= 90 ? 'text-green-600' : ($slaPct >= 80 ? 'text-yellow-600' : 'text-red-600'),
                    'waiting' => $waiting,
                    'calls' => $totalHandled,
                ];
            })
            ->filter(fn ($q) => $q['calls'] > 0 || $q['waiting'] > 0)
            ->sortByDesc('calls')
            ->values()
            ->take(8);

        return view('operations::livewire.control-tower.queue-table-widget', [
            'queues' => $queues,
        ]);
    }
}
