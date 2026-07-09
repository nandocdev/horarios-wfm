<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use Illuminate\Support\Facades\DB;
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

    public function render()
    {
        $stats = CallRecord::join('call_queues', 'call_records.queue_id', '=', 'call_queues.id')
            ->whereDate('ivr_started_at', $this->date)
            ->select(
                'call_queues.name as queue_name',
                'call_queues.aht_goal',
                DB::raw('COUNT(*) as total_offered'),
                DB::raw('SUM(CASE WHEN contact_disposition = 2 THEN 1 ELSE 0 END) as handled'),
                DB::raw('SUM(CASE WHEN contact_disposition = 1 THEN 1 ELSE 0 END) as abandoned'),
                DB::raw('AVG(talk_time + work_time) as avg_aht'),
                DB::raw('AVG(queue_time) as avg_asa'),
                DB::raw('MAX(queue_time) as max_wait'),
                // Nivel de Servicio estimado (asumiendo umbral de 20s para este ejemplo)
                DB::raw('SUM(CASE WHEN contact_disposition = 2 AND queue_time <= 20 THEN 1 ELSE 0 END) as sl_count')
            )
            ->groupBy('call_queues.id', 'call_queues.name', 'call_queues.aht_goal')
            ->orderBy('total_offered', 'desc')
            ->get();

        return view('operations::livewire.queue-performance-report', [
            'stats' => $stats,
        ])->layout('layouts.app', ['title' => 'Performance por Cola']);
    }
}
