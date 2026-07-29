<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class OperationalStatusWidget extends Component
{
    public array $employeeIds = [];

    public function placeholder()
    {
        return '<div class="h-64 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function render(TelemetryRealtimeRepositoryInterface $realtimeRepo)
    {
        $ids = $this->employeeIds;

        $states = $realtimeRepo->getRealtimeStates($ids);
        $connected = $states->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN']);
        $connectedCount = $connected->count();

        $total = max(1, $connectedCount);

        $grouped = $states->groupBy(fn ($s) => strtoupper(trim($s->current_state ?? 'UNKNOWN')));

        $statuses = [
            ['key' => 'TALKING', 'label' => 'En llamada', 'color' => 'bg-green-500', 'count' => $grouped->get('TALKING', collect())->count()],
            ['key' => 'READY', 'label' => 'Disponible', 'color' => 'bg-blue-500', 'count' => $grouped->get('READY', collect())->count()],
            ['key' => 'HOLD', 'label' => 'En espera', 'color' => 'bg-yellow-500', 'count' => $grouped->get('HOLD', collect())->count()],
            ['key' => 'WORK', 'label' => 'ACW', 'color' => 'bg-purple-500', 'count' => $grouped->get('WORK', collect())->count()],
            ['key' => 'NOT_READY', 'label' => 'Auxiliar', 'color' => 'bg-orange-500', 'count' => $grouped->get('NOT_READY', collect())->count()],
            ['key' => 'RESERVED', 'label' => 'Reservado', 'color' => 'bg-indigo-500', 'count' => $grouped->get('RESERVED', collect())->count()],
        ];

        $offlineCount = $states->count() - $connectedCount;

        foreach ($statuses as &$s) {
            $s['pct'] = round(($s['count'] / $total) * 100, 1);
        }

        return view('operations::livewire.control-tower.operational-status-widget', [
            'statuses' => $statuses,
            'total' => $connectedCount,
            'offlineCount' => $offlineCount,
        ]);
    }
}
