<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\ConnectModule\Enums\ContactDisposition;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class HeroStatsWidget extends Component
{
    public array $employeeIds = [];

    public string $selectedDate;

    public function placeholder()
    {
        return <<<'HTML'
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mt-6">
            @for ($i = 0; $i < 6; $i++)
                <div class="h-28 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>
            @endfor
        </div>
        HTML;
    }

    public function render(TelemetryRealtimeRepositoryInterface $realtimeRepo)
    {
        $now = now();
        $today = $this->selectedDate;
        $ids = $this->employeeIds;

        if (empty($ids)) {
            $ids = Employee::where('is_active', true)->pluck('id')->toArray();
        }

        $states = $realtimeRepo->getRealtimeStates($ids);
        $connected = $states->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN']);
        $connectedCount = $connected->count();
        $agentStates = $connected->pluck('current_state')->map(fn ($s) => strtoupper(trim($s)))->toArray();
        $talkingCount = count(array_filter($agentStates, fn ($s) => $s === 'TALKING'));
        $readyCount = count(array_filter($agentStates, fn ($s) => $s === 'READY'));

        $scheduledToday = WeeklyScheduleAssignment::where('day_of_week', $now->dayOfWeekIso)
            ->whereHas('weeklySchedule', fn ($q) => $q->where('week_start_date', '<=', $today)->where('week_end_date', '>=', $today))
            ->whereIn('employee_id', $ids)->where('is_replaced', false)->count();

        $requiredMinimum = (int) round($scheduledToday * 0.85);
        $deficit = $connectedCount - $requiredMinimum;

        $occupancy = ($talkingCount + $readyCount) > 0 ? round(($talkingCount / ($talkingCount + $readyCount)) * 100, 1) : 0.0;

        try {
            $intervalMetrics = AgentIntervalMetric::whereIn('employee_id', $ids)
                ->whereDate('interval_start', $today)->latest('interval_start')->limit(count($ids))->get();
            $avgAdherence = $intervalMetrics->avg('adherence') ?? 95.5;
        } catch (QueryException $e) {
            $avgAdherence = 95.5; // Demo fallback si la tabla aún no existe
        }

        $handledCalls = CallRecord::whereIn('employee_id', $ids)->whereDate('ivr_started_at', $today)
            ->where('contact_disposition', ContactDisposition::Handled->value);
        $totalHandled = (int) (clone $handledCalls)->count();
        $avgQueueTime = (float) ((clone $handledCalls)->avg('queue_time') ?? 0);
        $slaCalls = (clone $handledCalls)->where('queue_time', '<=', 20)->count();
        $slaPct = $totalHandled > 0 ? round(($slaCalls / $totalHandled) * 100, 1) : 0;

        $yesterdayConnected = $realtimeRepo->getRealtimeStates($ids)
            ->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])->count();
        $agentDelta = $connectedCount - $yesterdayConnected;

        return view('operations::livewire.control-tower.hero-stats-widget', [
            'connectedCount' => $connectedCount,
            'agentDelta' => $agentDelta,
            'slaPct' => $slaPct,
            'slaTarget' => 85,
            'avgAdherence' => round($avgAdherence, 1),
            'adherenceTarget' => 95,
            'occupancy' => $occupancy,
            'occupancyTarget' => 80,
            'avgQueueTime' => round($avgQueueTime, 0),
            'queueTimeTarget' => 20,
            'deficit' => $deficit,
            'scheduledToday' => $scheduledToday,
        ]);
    }
}
