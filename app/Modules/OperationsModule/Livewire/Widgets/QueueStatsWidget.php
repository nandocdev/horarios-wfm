<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\Widgets;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

    public function render()
    {
        $date = Carbon::parse($this->selectedDate);
        $stats = [];

        try {
            if (! $date->isToday()) {
                $stats = CallRecord::join('call_queues', 'call_records.queue_id', '=', 'call_queues.id')
                    ->whereDate('ivr_started_at', $this->selectedDate)
                    ->select(
                        'call_queues.name',
                        DB::raw('0 as waiting'),
                        DB::raw('0 as lwt'),
                        DB::raw('AVG(CASE WHEN contact_disposition = 2 THEN 100 ELSE 0 END) as sl'),
                        DB::raw('0 as talking'),
                        DB::raw('COUNT(*) as received'),
                        DB::raw('SUM(CASE WHEN contact_disposition = 2 THEN 1 ELSE 0 END) as handled'),
                        DB::raw('SUM(CASE WHEN contact_disposition = 1 THEN 1 ELSE 0 END) as abandoned'),
                        DB::raw("'neutral' as status")
                    )
                    ->groupBy('call_queues.name')
                    ->get()
                    ->map(fn ($item) => (array) $item)
                    ->toArray();
            } else {
                $operatorIds = Employee::whereIn('position_id', [1, 2, 5, 11, 13])->pluck('id')->toArray();

                $talkingByQueue = collect();
                try {
                    $talkingByQueue = AgentRealtimeState::whereIn('employee_id', $operatorIds)
                        ->where('current_state', 'TALKING')
                        ->get()
                        ->groupBy(function ($agent) {
                            return $agent->metadata['call_info']['queue_name'] ?? 'OTROS';
                        })
                        ->map->count();
                } catch (\Exception $e) {
                    // Fallback silencioso para el conteo de agentes hablando
                }

                $stats = DB::table('csq_realtime_stats')
                    ->where('total_calls_since_midnight', '>', 0)
                    ->orderByDesc('calls_waiting')
                    ->get()
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
            // Fallback silencioso global del widget en caso de caídas del CTI
            $stats = [];
        }

        return view('operations::livewire.widgets.queue-stats-widget', [
            'queueStats' => $stats,
        ]);
    }
}
