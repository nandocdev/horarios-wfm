<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\ConnectModule\Models\CsqRealtimeStat;
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

        $callStats = DB::table('call_records')
            ->join('call_queues', 'call_queues.id', '=', 'call_records.queue_id')
            ->whereDate('call_records.ivr_started_at', $today)
            ->whereNotNull('call_records.queue_id')
            ->select(
                'call_queues.name as queue_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(*) FILTER (WHERE call_records.contact_disposition = 2) as handled'),
                DB::raw('COUNT(*) FILTER (WHERE call_records.contact_disposition IN (1, 4, 13)) as abandoned'),
                DB::raw('COUNT(*) FILTER (WHERE call_records.contact_disposition = 2 AND call_records.queue_time <= 20) as sla_calls'),
                DB::raw('AVG(call_records.talk_time + call_records.work_time) FILTER (WHERE call_records.contact_disposition = 2) as avg_aht'),
                DB::raw('AVG(call_records.queue_time) FILTER (WHERE call_records.contact_disposition IN (1, 4, 13)) as avg_abandon_time'),
                DB::raw('MAX(call_records.queue_time) as max_queue_time'),
            )
            ->groupBy('call_queues.name')
            ->get()
            ->keyBy('queue_name');

        $realtime = CsqRealtimeStat::all()->keyBy('csq_name');

        $allNames = $callStats->keys()
            ->merge($realtime->keys())
            ->unique()
            ->sort()
            ->values();

        $queues = $allNames->map(function ($name) use ($callStats, $realtime) {
            $s = $callStats->get($name);
            $r = $realtime->get($name);

            $total = (int) ($s->total ?? 0);
            $handled = (int) ($s->handled ?? 0);
            $abandoned = (int) ($s->abandoned ?? 0);
            $slaCount = (int) ($s->sla_calls ?? 0);
            $waiting = (int) ($r->calls_waiting ?? 0);
            $avgAht = (float) ($s->avg_aht ?? 0);
            $avgAbandonTime = (float) ($s->avg_abandon_time ?? 0);
            $maxWait = (int) ($s->max_queue_time ?? 0);
            $slaPct = $handled > 0 ? round(($slaCount / $handled) * 100, 1) : ($r && $r->service_level_long_term !== null ? round($r->service_level_long_term, 1) : 0);

            return [
                'name' => $name,
                'recibidas' => $total,
                'atendidas' => $handled,
                'abandonadas' => $abandoned,
                'espera' => $waiting,
                'tmo_abandono' => $avgAbandonTime > 0 ? round($avgAbandonTime, 1) : null,
                'aht' => $avgAht > 0 ? round($avgAht, 1) : null,
                'max_espera' => $maxWait,
                'sla' => $slaPct,
                'slaClass' => $slaPct >= 90 ? 'text-green-600' : ($slaPct >= 80 ? 'text-yellow-600' : 'text-red-600'),
            ];
        })->filter(fn ($q) => $q['recibidas'] > 0 || $q['espera'] > 0 || $q['atendidas'] > 0)
            ->sortByDesc('recibidas')
            ->values()
            ->take(12);

        return view('operations::livewire.control-tower.queue-table-widget', [
            'queues' => $queues,
        ]);
    }
}
