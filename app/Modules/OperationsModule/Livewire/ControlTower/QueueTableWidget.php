<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\ConnectModule\Models\CsqRealtimeStat;
use App\Shared\Support\Metrics\ServiceQualityMetrics;
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

    public function mount(string $selectedDate): void
    {
        $this->selectedDate = $selectedDate;
    }

    public function render()
    {
        $today = $this->selectedDate;
        $isToday = $today === now()->toDateString();

        // Acumulado del día desde call_records (detalle completo: AHT, tiempos de cola).
        // [NOTA] CiscoSync importa call_records una vez al día (05:00); durante la jornada
        // esta fuente puede ir rezagada respecto a los contadores de CUIC.
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

        // Snapshot CUIC sincronizado cada 5 min (cuic:sync). Sus contadores
        // *_since_midnight son el acumulado del día hasta ahora.
        $realtime = CsqRealtimeStat::all()->keyBy('csq_name');

        $allNames = $callStats->keys()
            ->merge($realtime->keys())
            ->unique()
            ->sort()
            ->values();

        $queues = $allNames->map(function ($name) use ($callStats, $realtime, $isToday) {
            $s = $callStats->get($name);
            $r = $realtime->get($name);

            // Acumulado del día: máximo entre call_records y los contadores
            // desde medianoche de CUIC (la fuente más fresca gana; nunca se suman
            // porque cuentan las mismas llamadas).
            $recordTotal = (int) ($s->total ?? 0);
            $recordHandled = (int) ($s->handled ?? 0);
            $recordAbandoned = (int) ($s->abandoned ?? 0);
            $slaCount = (int) ($s->sla_calls ?? 0);

            if ($r !== null && $isToday) {
                $total = max($recordTotal, (int) $r->total_calls_since_midnight);
                $handled = max($recordHandled, (int) $r->calls_handled_since_midnight);
                $abandoned = max($recordAbandoned, (int) $r->calls_abandoned_since_midnight);
            } else {
                $total = $recordTotal;
                $handled = $recordHandled;
                $abandoned = $recordAbandoned;
            }

            $waiting = (int) ($r->calls_waiting ?? 0);
            $avgAht = (float) ($s->avg_aht ?? 0);
            $avgAbandonTime = (float) ($s->avg_abandon_time ?? 0);
            $maxWait = (int) ($s->max_queue_time ?? 0);

            // SLA del día solo si hay detalle en call_records; si no, cae al valor
            // rodante de CUIC (evita mostrar 0% falso con contadores sin detalle).
            $slaPct = $recordTotal > 0
                ? ServiceQualityMetrics::serviceLevel($slaCount, $recordTotal)
                : ($r && $r->service_level_long_term !== null ? round($r->service_level_long_term, 1) : 0);

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
