<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Console\Commands;

use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Schedules\ScheduleServiceInterface;
use App\Shared\Support\Metrics\RealtimeMetrics;
use App\Shared\Support\Metrics\ServiceQualityMetrics;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillIntervalMetricsCommand extends Command
{
    protected $signature = 'operations:backfill-interval-metrics
        {--from= : Fecha inicio (YYYY-MM-DD). Por defecto: primera fecha con datos}
        {--to= : Fecha fin (YYYY-MM-DD). Por defecto: ayer}';

    protected $description = 'Genera métricas de intervalo (15 min) para el histórico de transiciones de agentes (modo masivo)';

    public function handle(
        ScheduleServiceInterface $scheduleService,
    ): int {
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : null;
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::yesterday();

        $minDate = $from ?? AgentStateTransition::min('transition_time');
        if (! $minDate) {
            $this->error('No hay transiciones en la base de datos.');

            return self::FAILURE;
        }

        $startDate = Carbon::parse($minDate)->startOfDay();
        $endDate = Carbon::parse($to)->endOfDay();

        $this->info('Backfill masivo de métricas de intervalo');
        $this->info("Rango: {$startDate->toDateString()} → {$endDate->toDateString()}");
        $this->newLine();

        $employees = Employee::where('is_active', true)->pluck('id')->toArray();
        if (empty($employees)) {
            $this->error('No hay empleados activos.');

            return self::FAILURE;
        }

        $this->info('Empleados activos: '.count($employees));
        $this->newLine();

        // Pre-cargar schedules de todo el rango una sola vez
        $this->info('Cargando schedules...');
        $schedulesCache = $this->preLoadSchedules($employees, $startDate, $endDate);

        $current = $startDate->copy();
        $totalGenerated = 0;
        $totalSkipped = 0;

        while ($current->lte($endDate)) {
            $dayStr = $current->toDateString();

            $hasData = AgentStateTransition::whereDate('transition_time', $dayStr)->exists();
            if (! $hasData) {
                $current = $current->addDay();

                continue;
            }

            $this->info("Procesando {$dayStr}...");

            $dayGenerated = 0;
            $daySkipped = 0;
            $rows = [];

            // Procesar empleado por empleado para no agotar memoria
            foreach ($employees as $employeeId) {
                $schedule = $schedulesCache[$employeeId][$dayStr] ?? null;
                if (! $schedule) {
                    $daySkipped += 96;

                    continue;
                }

                // Cargar transiciones de UN solo empleado para UN día
                $transitions = AgentStateTransition::where('employee_id', $employeeId)
                    ->whereDate('transition_time', $dayStr)
                    ->orderBy('transition_time')
                    ->get();

                if ($transitions->isEmpty()) {
                    $daySkipped += 96;

                    continue;
                }

                // Cargar llamadas de UN solo empleado para UN día
                $calls = CallRecord::where('employee_id', $employeeId)
                    ->whereDate('ivr_started_at', $dayStr)
                    ->get();

                $shiftStart = Carbon::parse($schedule['start_time'])->setDate($current->year, $current->month, $current->day);
                $shiftEnd = Carbon::parse($schedule['end_time'])->setDate($current->year, $current->month, $current->day);
                if ($shiftEnd->lessThan($shiftStart)) {
                    $shiftEnd = $shiftEnd->addDay();
                }

                $intervalStart = $current->copy()->startOfDay();
                $dayEnd = $current->copy()->endOfDay();

                while ($intervalStart->lt($dayEnd)) {
                    $intervalEnd = $intervalStart->copy()->addMinutes(15);

                    $overlapStart = max($intervalStart->getTimestamp(), $shiftStart->getTimestamp());
                    $overlapEnd = min($intervalEnd->getTimestamp(), $shiftEnd->getTimestamp());
                    $scheduledSeconds = max(0, $overlapEnd - $overlapStart);

                    if ($scheduledSeconds <= 0) {
                        $intervalStart = $intervalEnd;

                        continue;
                    }

                    $durations = $this->calculateStateDurations($transitions, $intervalStart, $intervalEnd);
                    $productiveSeconds = $durations['talk'] + $durations['hold'] + $durations['wrap'];
                    $loggedSeconds = $productiveSeconds + $durations['ready'] + $durations['not_ready'];

                    $occupancy = RealtimeMetrics::occupancy(
                        (float) $durations['talk'],
                        (float) $durations['hold'],
                        (float) $durations['wrap'],
                        (float) $loggedSeconds,
                        (float) $durations['not_ready']
                    );
                    $utilization = RealtimeMetrics::utilization(
                        (float) $productiveSeconds / 60,
                        (float) $scheduledSeconds / 60
                    );
                    $adherence = RealtimeMetrics::adherenceRate(
                        (float) $productiveSeconds,
                        (float) $scheduledSeconds
                    );

                    $intervalCalls = $calls->filter(fn ($c) => Carbon::parse($c->ivr_started_at)->gte($intervalStart) &&
                        Carbon::parse($c->ivr_started_at)->lt($intervalEnd)
                    );
                    $callsHandled = $intervalCalls->count();
                    $aht = ServiceQualityMetrics::aht(
                        (float) $intervalCalls->sum('talk_time'),
                        (float) $intervalCalls->sum('hold_time'),
                        (float) $intervalCalls->sum('work_time'),
                        $callsHandled
                    );

                    $rows[] = [
                        'id' => (string) Str::ulid(),
                        'employee_id' => $employeeId,
                        'interval_start' => $intervalStart->toDateTimeString(),
                        'interval_end' => $intervalEnd->toDateTimeString(),
                        'talk_seconds' => $durations['talk'],
                        'hold_seconds' => $durations['hold'],
                        'ready_seconds' => $durations['ready'],
                        'not_ready_seconds' => $durations['not_ready'],
                        'wrap_seconds' => $durations['wrap'],
                        'calls_handled' => $callsHandled,
                        'aht_seconds' => $aht,
                        'occupancy' => $occupancy,
                        'utilization' => $utilization,
                        'adherence' => $adherence,
                        'queue_distribution' => null,
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ];
                    $dayGenerated++;

                    $intervalStart = $intervalEnd;
                }
            }

            // Insert masivo en lotes
            if (! empty($rows)) {
                foreach (array_chunk($rows, 500) as $batch) {
                    DB::table('agent_interval_metrics')->insert($batch);
                }
            }

            $totalGenerated += $dayGenerated;
            $totalSkipped += $daySkipped;

            $this->line("  → {$dayGenerated} generados, {$daySkipped} sin turno");

            // Liberar memoria
            gc_collect_cycles();
            $current = $current->addDay();
        }

        $this->newLine(2);
        $this->table(['Métrica', 'Valor'], [
            ['Registros generados', $totalGenerated],
            ['Intervalos sin turno', $totalSkipped],
        ]);

        $this->newLine();
        $this->info('Backfill completado.');

        return self::SUCCESS;
    }

    private function preLoadSchedules(array $employeeIds, CarbonInterface $from, CarbonInterface $to): array
    {
        $cache = [];

        // Obtener todos los WeeklyScheduleAssignment en el rango
        $assignments = WeeklyScheduleAssignment::whereHas('weeklySchedule', function ($q) use ($from, $to) {
            $q->where('week_end_date', '>=', $from->toDateString())
                ->where('week_start_date', '<=', $to->toDateString());
        })->whereIn('employee_id', $employeeIds)
            ->with('weeklySchedule')
            ->get();

        foreach ($assignments as $a) {
            $ws = $a->weeklySchedule;
            if (! $ws) {
                continue;
            }

            $weekStart = Carbon::parse($ws->week_start_date);
            $weekEnd = Carbon::parse($ws->week_end_date);

            $current = $weekStart->copy();
            while ($current->lte($weekEnd) && $current->lte($to)) {
                if ($current->gte($from)) {
                    $dayStr = $current->toDateString();
                    $cache[$a->employee_id][$dayStr] = [
                        'start_time' => $a->start_time instanceof \DateTimeInterface ? $a->start_time->format('H:i:s') : $a->start_time,
                        'end_time' => $a->end_time instanceof \DateTimeInterface ? $a->end_time->format('H:i:s') : $a->end_time,
                    ];
                }
                $current = $current->addDay();
            }
        }

        return $cache;
    }

    private function calculateStateDurations($transitions, CarbonInterface $start, CarbonInterface $end): array
    {
        $durations = [
            'talk' => 0,
            'hold' => 0,
            'ready' => 0,
            'not_ready' => 0,
            'wrap' => 0,
        ];

        if ($transitions->isEmpty()) {
            return $durations;
        }

        $currentTime = $start->copy();
        $currentState = $this->mapState($transitions->first()->agent_state);

        foreach ($transitions as $i => $transition) {
            $transitionTime = Carbon::parse($transition->transition_time);

            if ($i === 0) {
                // Primera transición: si es anterior al intervalo, usar como estado inicial
                if ($transitionTime->lt($start)) {
                    continue;
                }
            }

            if ($transitionTime->gt($currentTime) && $transitionTime->lte($end)) {
                $seconds = (int) abs($transitionTime->diffInSeconds($currentTime));
                $this->addToDuration($durations, $currentState, $seconds);
                $currentTime = $transitionTime;
            }

            $currentState = $this->mapState($transition->agent_state);
        }

        if ($currentTime->lt($end)) {
            $remaining = (int) abs($currentTime->diffInSeconds($end));
            $this->addToDuration($durations, $currentState, $remaining);
        }

        return $durations;
    }

    private function mapState(string $agentState): string
    {
        return match (strtoupper(trim($agentState))) {
            'TALKING' => 'talk',
            'HOLD' => 'hold',
            'READY', 'RESERVED' => 'ready',
            'NOT_READY', 'AUX' => 'not_ready',
            'WORK' => 'wrap',
            default => 'not_ready',
        };
    }

    private function addToDuration(array &$durations, string $state, int $seconds): void
    {
        if (isset($durations[$state])) {
            $durations[$state] += $seconds;
        }
    }
}
