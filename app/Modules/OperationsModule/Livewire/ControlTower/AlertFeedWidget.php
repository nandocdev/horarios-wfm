<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\WfmModule\Models\OperationalSetting;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class AlertFeedWidget extends Component
{
    public array $employeeIds = [];

    public function placeholder()
    {
        return '<div class="h-64 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function render(
        DashboardScheduleQueriesInterface $scheduleQueries,
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
    ) {
        $today = now()->toDateString();
        $ids = $this->employeeIds;
        $alerts = collect();

        $queues = $realtimeRepo->getQueueStats(0);
        $queues->each(function ($q) use ($alerts) {
            $level = $q['state'] === 'critical' ? 'critical' : ($q['state'] === 'attention' ? 'warning' : null);
            if ($level) {
                $alerts->push([
                    'level' => $level,
                    'icon' => $level === 'critical' ? 'exclamation-triangle' : 'exclamation-circle',
                    'category' => $q['name'] ?? 'Cola',
                    'message' => $q['state'] === 'critical'
                        ? "SLA menor a {$q['sla']}%"
                        : "SLA en atención ({$q['sla']}%)",
                ]);
            }
        });

        $exceptionsCount = $scheduleQueries->getExceptionCount($ids, $today);
        if ($exceptionsCount > 0) {
            $alerts->push([
                'level' => 'warning',
                'icon' => 'clock',
                'category' => 'Ausentismo',
                'message' => "{$exceptionsCount} empleados con excepción de horario hoy",
            ]);
        }

        $scheduled = $scheduleQueries->getScheduledCount($ids, $today, now()->dayOfWeekIso);
        $states = $realtimeRepo->getRealtimeStates($ids);
        $connected = $states->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])->count();
        $coveragePct = $scheduled > 0 ? round(($connected / $scheduled) * 100, 1) : 0;

        $coverageGoal = (int) (OperationalSetting::where('key', 'goal_coverage')->value('value') ?? 85);
        if ($coveragePct < $coverageGoal) {
            $alerts->push([
                'level' => 'critical',
                'icon' => 'chart-bar',
                'category' => 'Cobertura',
                'message' => "Cobertura {$coveragePct}% vs objetivo {$coverageGoal}%",
            ]);
        }

        if ($alerts->isEmpty()) {
            $alerts->push([
                'level' => 'info',
                'icon' => 'check-circle',
                'category' => 'Operación',
                'message' => 'Sin novedades — operación normal',
            ]);
        }

        return view('operations::livewire.control-tower.alert-feed-widget', [
            'alerts' => $alerts->take(6),
        ]);
    }
}
