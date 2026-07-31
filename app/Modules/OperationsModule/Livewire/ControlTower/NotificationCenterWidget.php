<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\AnalyticsModule\Models\ForecastScenario;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use App\Shared\Support\Metrics\ServiceQualityMetrics;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class NotificationCenterWidget extends Component
{
    public array $employeeIds = [];

    public string $selectedDate;

    public function placeholder()
    {
        return '<div class="h-48 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function render()
    {
        $today = Carbon::parse($this->selectedDate);
        $notifications = collect();

        $latestForecast = ForecastScenario::whereHas('version', fn ($q) => $q->where('status', 'published'))
            ->latest()->first();
        if ($latestForecast) {
            $notifications->push(['icon' => 'chart-bar', 'text' => 'Nueva versión de Forecast publicada', 'time' => $latestForecast->created_at->diffForHumans()]);
        }

        $callStats = CallRecord::whereDate('ivr_started_at', $today)
            ->selectRaw('COUNT(*) as total_offered, SUM(CASE WHEN contact_disposition = 2 AND queue_time <= 20 THEN 1 ELSE 0 END) as sla_calls')
            ->first();
        $slaPct = ServiceQualityMetrics::serviceLevel((int) $callStats->sla_calls, (int) $callStats->total_offered);
        if ($slaPct >= 90) {
            $notifications->push(['icon' => 'check-circle', 'text' => "SLA recuperado ({$slaPct}%)", 'time' => 'Ahora']);
        }

        $futureActivities = IntradayActivity::whereHas('activityType', fn ($q) => $q->where('name', 'like', '%coach%'))
            ->whereRaw('lower(time_range)::date = ?', [$today->toDateString()])->count();
        if ($futureActivities > 0) {
            $notifications->push(['icon' => 'academic-cap', 'text' => "{$futureActivities} coaching(s) programados hoy", 'time' => 'Hoy']);
        }

        $teamsWithoutCoverage = Team::where('is_active', true)->get()->filter(function ($team) use ($today) {
            $ids = Employee::where('team_id', $team->id)->where('is_active', true)->pluck('id');
            $scheduledCount = WeeklyScheduleAssignment::whereIn('employee_id', $ids)
                ->where('day_of_week', now()->dayOfWeekIso)->where('is_replaced', false)
                ->whereHas('weeklySchedule', fn ($q) => $q->where('week_start_date', '<=', $today)->where('week_end_date', '>=', $today))
                ->count();
            $required = max(1, (int) round($scheduledCount * 0.85));
            $states = app(TelemetryRealtimeRepositoryInterface::class)->getRealtimeStates($ids->toArray());
            $connected = $states->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])->count();

            return $connected < $required;
        });

        foreach ($teamsWithoutCoverage as $team) {
            $notifications->push(['icon' => 'exclamation-triangle', 'text' => "{$team->name} sin cobertura suficiente", 'time' => 'Ahora']);
        }

        return view('operations::livewire.control-tower.notification-center-widget', [
            'notifications' => $notifications->take(5),
        ]);
    }
}
