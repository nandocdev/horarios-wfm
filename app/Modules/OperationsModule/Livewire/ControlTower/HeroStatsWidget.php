<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\ConnectModule\Enums\ContactDisposition;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use App\Shared\Support\Metrics\RealtimeMetrics;
use App\Shared\Support\Metrics\ServiceQualityMetrics;
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

        try {
            $intervalQuery = AgentIntervalMetric::whereIn('employee_id', $ids)
                ->whereDate('interval_start', $today);

            $avgAdherence = (float) (clone $intervalQuery)->avg('adherence');
            $avgOccupancy = (float) (clone $intervalQuery)->avg('occupancy');
        } catch (QueryException $e) {
            $avgAdherence = 0;
            $avgOccupancy = 0;
        }

        // Occupancy en tiempo real (agentes conectados vs programados)
        $occupancyRealtime = RealtimeMetrics::occupancy($talkingCount, 0, 0, $talkingCount + $readyCount, 0);
        // Usar el mayor entre el cálculo en tiempo real y el promedio de intervalos
        $occupancy = round(max($occupancyRealtime, $avgOccupancy), 1);

        $handledCalls = CallRecord::whereIn('employee_id', $ids)->whereDate('ivr_started_at', $today)
            ->where('contact_disposition', ContactDisposition::Handled->value);
        $totalHandled = (int) (clone $handledCalls)->count();
        $avgQueueTime = (float) ((clone $handledCalls)->avg('queue_time') ?? 0);
        // Para el SLA total, no filtramos por employee_id ya que los abandonos en cola no tienen agente asignado.
        $globalCallStats = CallRecord::whereDate('ivr_started_at', $today)
            ->selectRaw('COUNT(*) as total_offered, SUM(CASE WHEN contact_disposition = 2 AND queue_time <= 20 THEN 1 ELSE 0 END) as sla_calls')
            ->first();
        $slaPct = ServiceQualityMetrics::serviceLevel((int) $globalCallStats->sla_calls, (int) $globalCallStats->total_offered);

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
