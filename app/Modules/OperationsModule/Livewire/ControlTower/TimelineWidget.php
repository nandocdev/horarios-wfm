<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class TimelineWidget extends Component
{
    public ?string $selectedDate = null;

    public function render()
    {
        $today = $this->selectedDate ?? now()->toDateString();

        // Registro cronológico de eventos operativos del día (alertas persistidas
        // por alerts:evaluate). El feed de actividad (ActivityFeedWidget) cubre las
        // solicitudes de RRHH; este timeline cubre lo operativo.
        $events = DB::table('alert_events')
            ->join('alert_rules', 'alert_rules.id', '=', 'alert_events.alert_rule_id')
            ->whereDate('alert_events.first_triggered_at', $today)
            ->orderByDesc('alert_events.first_triggered_at')
            ->limit(10)
            ->get([
                'alert_events.level',
                'alert_events.message',
                'alert_events.first_triggered_at',
                'alert_rules.label as rule_label',
            ])
            ->map(fn ($event) => [
                'time' => Carbon::parse($event->first_triggered_at)->format('H:i'),
                'title' => $event->message !== null && $event->message !== '' ? $event->message : $event->rule_label,
                'type' => match ($event->level) {
                    'critical' => 'critical',
                    'warning' => 'warning',
                    default => 'info',
                },
            ]);

        return view('operations::livewire.control-tower.timeline-widget', [
            'events' => $events,
        ]);
    }
}
