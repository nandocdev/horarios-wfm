<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Shared\Support\CallQueueCache;
use Illuminate\Support\Facades\DB;
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

        $stats = DB::table('call_records')
            ->select('queue_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(*) FILTER (WHERE contact_disposition = 2) as handled'),
                DB::raw('COUNT(*) FILTER (WHERE contact_disposition = 2 AND queue_time <= 20) as sla_calls'),
            )
            ->whereDate('ivr_started_at', $today)
            ->whereNotNull('queue_id')
            ->groupBy('queue_id')
            ->get()
            ->keyBy('queue_id');

        $queues = app(CallQueueCache::class)->active()
            ->map(function ($queue) use ($stats) {
                $s = $stats->get($queue->id);
                $handled = (int) ($s->handled ?? 0);
                $total = (int) ($s->total ?? 0);
                $waiting = max(0, $total - $handled);
                $slaPct = $handled > 0 ? round((($s->sla_calls ?? 0) / $handled) * 100, 1) : 0;

                return [
                    'name' => $queue->name,
                    'sla' => $slaPct,
                    'slaClass' => $slaPct >= 90 ? 'text-green-600' : ($slaPct >= 80 ? 'text-yellow-600' : 'text-red-600'),
                    'waiting' => $waiting,
                    'calls' => $handled,
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
