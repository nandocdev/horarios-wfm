<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\Widgets;

use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class QueueStatsWidget extends Component
{
    public string $selectedDate;

    #[Computed]
    public function isHistorical(): bool
    {
        return $this->selectedDate !== now()->toDateString();
    }

    public function mount(string $selectedDate): void
    {
        $this->selectedDate = $selectedDate;
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="h-[400px] bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl animate-pulse"></div>
        HTML;
    }

    public function render(
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
    ) {
        $date = Carbon::parse($this->selectedDate);
        $stats = [];

        try {
            if (! $date->isToday()) {
                $stats = $realtimeRepo->getQueuePerformanceReport($this->selectedDate)
                    ->filter(fn ($row) => ($row->total_offered ?? 0) > 0 || ($row->handled ?? 0) > 0)
                    ->map(fn ($row) => [
                        'name' => $row->queue_name,
                        'waiting' => 0,
                        'lwt' => 0,
                        'sl' => $row->total_offered > 0
                            ? round(($row->handled / $row->total_offered) * 100, 1)
                            : 0,
                        'talking' => 0,
                        'received' => (int) $row->total_offered,
                        'handled' => (int) $row->handled,
                        'abandoned' => (int) $row->abandoned,
                        'status' => 'neutral',
                    ])
                    ->toArray();
            } else {
                $operatorIds = Employee::whereIn('position_id', [1, 2, 5, 11, 13])->pluck('id')->toArray();

                $talkingByQueue = collect();
                try {
                    $talkingByQueue = $realtimeRepo->getTalkingAgentsByQueue($operatorIds);
                } catch (\Exception $e) {
                }

                $stats = $realtimeRepo->getCsqRealtimeStats()
                    ->filter(fn ($csq) => $csq->total_calls_since_midnight > 0)
                    ->map(function ($csq) use ($talkingByQueue) {
                        return [
                            'name' => $csq->csq_name,
                            'waiting' => $csq->calls_waiting,
                            'lwt' => $csq->longest_call_in_queue,
                            'sl' => $csq->service_level_long_term,
                            'talking' => $talkingByQueue->get($csq->csq_name, 0),
                            'received' => $csq->total_calls_since_midnight,
                            'handled' => $csq->calls_handled_since_midnight,
                            'abandoned' => $csq->calls_abandoned_since_midnight,
                            'status' => $csq->calls_waiting > 5 ? 'danger' : ($csq->calls_waiting > 2 ? 'warning' : 'success'),
                        ];
                    })
                    ->toArray();

                $otherTalking = $talkingByQueue->get('OTROS', 0);
                if ($otherTalking > 0) {
                    $stats[] = [
                        'name' => 'LLAMADAS DIRECTAS / SALIENTES',
                        'waiting' => 0,
                        'lwt' => 0,
                        'sl' => 100,
                        'talking' => $otherTalking,
                        'received' => 0,
                        'handled' => 0,
                        'abandoned' => 0,
                        'status' => 'success',
                    ];
                }
            }
        } catch (\Exception $e) {
            $stats = [];
        }

        return view('operations::livewire.widgets.queue-stats-widget', [
            'queueStats' => $stats,
        ]);
    }
}
