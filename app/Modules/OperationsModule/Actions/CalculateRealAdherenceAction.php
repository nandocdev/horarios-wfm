<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Actions;

use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Shared\Contracts\Schedules\ScheduleServiceInterface;
use App\Shared\Support\Metrics\MetricFormulas;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Acción para calcular la adherencia real e histórica cruzando cronogramas vs telemetría.
 */
final class CalculateRealAdherenceAction
{
    public function __construct(
        private readonly ScheduleServiceInterface $scheduleService
    ) {}

    /**
     * Calcula la adherencia para un empleado en un rango de fechas.
     */
    public function execute(Employee $employee, CarbonInterface $startDate, ?CarbonInterface $endDate = null): array
    {
        $endDate = $endDate ?? $startDate;
        $current = $startDate->copy();
        
        $totalScheduledSeconds = 0;
        $totalAdherentSeconds = 0;
        $dailyResults = [];

        while ($current->lte($endDate)) {
            $result = $this->calculateForDay($employee, $current);
            
            $totalScheduledSeconds += $result['scheduled_seconds'];
            $totalAdherentSeconds += $result['adherent_seconds'];
            
            $dailyResults[$current->toDateString()] = $result;
            
            $current->addDay();
        }

        $globalPercentage = $totalScheduledSeconds > 0 
            ? round(($totalAdherentSeconds / $totalScheduledSeconds) * 100, 1) 
            : 100.0;

        return [
            'percentage' => $globalPercentage,
            'scheduled_seconds' => $totalScheduledSeconds,
            'adherent_seconds' => $totalAdherentSeconds,
            'days' => $dailyResults
        ];
    }

    /**
     * Calcula la adherencia para un grupo de empleados en una fecha.
     */
    public function executeBatch(array $employees, CarbonInterface $date): array
    {
        $totalScheduled = 0;
        $totalAdherent = 0;

        foreach ($employees as $employee) {
            $res = $this->calculateForDay($employee, $date);
            $totalScheduled += $res['scheduled_seconds'];
            $totalAdherent += $res['adherent_seconds'];
        }

        return [
            'percentage' => $totalScheduled > 0 ? round(($totalAdherent / $totalScheduled) * 100, 1) : 100.0,
            'scheduled_seconds' => $totalScheduled,
            'adherent_seconds' => $totalAdherent,
        ];
    }

    /**
     * Lógica central de cálculo por día.
     */
    private function calculateForDay(Employee|int $employee, CarbonInterface $date): array
    {
        $employeeId = $employee instanceof Employee ? $employee->id : $employee;
        $isToday = $date->isToday();
        $now = now();
        $limitTime = $isToday ? $now : $date->copy()->endOfDay();

        // 1. Obtener Línea de Tiempo Esperada (Planificada)
        $expectedSegments = $this->getExpectedTimeline((int) $employeeId, $date, $limitTime);
        
        // 2. Obtener Línea de Tiempo Real (Telemetría)
        $realSegments = $this->getRealTimeline((int) $employeeId, $date);

        $totalScheduled = 0;
        $totalAdherent = 0;

        foreach ($expectedSegments as $expected) {
            $duration = $expected['end']->getTimestamp() - $expected['start']->getTimestamp();
            $totalScheduled += $duration;

            // Encontrar segmentos reales que intersectan con este segmento esperado
            foreach ($realSegments as $real) {
                // Intersección de intervalos [max(s1, s2), min(e1, e2)]
                $intersectStart = $expected['start']->max($real['start']);
                $intersectEnd = $expected['end']->min($real['end']);

                if ($intersectStart->lt($intersectEnd)) {
                    $intersectDuration = $intersectEnd->getTimestamp() - $intersectStart->getTimestamp();
                    
                    if (MetricFormulas::checkAdherence($real['state'], $expected['type'])) {
                        $totalAdherent += $intersectDuration;
                    }
                }
            }
        }

        return [
            'percentage' => $totalScheduled > 0 ? round(($totalAdherent / $totalScheduled) * 100, 1) : 100.0,
            'scheduled_seconds' => $totalScheduled,
            'adherent_seconds' => $totalAdherent,
        ];
    }

    /**
     * Construye los segmentos de tiempo esperados, manejando prioridades (Exception > Intraday > Shift).
     */
    private function getExpectedTimeline(int $employeeId, CarbonInterface $date, CarbonInterface $limitTime): array
    {
        $dayInfo = $this->scheduleService->getScheduleForEmployee($employeeId, $date);
        $segments = [];

        if ($dayInfo->is_off || !$dayInfo->start_time) {
            return [];
        }

        // --- A. Base Shift ---
        $shiftStart = Carbon::parse($dayInfo->start_time)->setDate($date->year, $date->month, $date->day);
        $shiftEnd = Carbon::parse($dayInfo->end_time)->setDate($date->year, $date->month, $date->day);
        if ($shiftEnd->lt($shiftStart)) { $shiftEnd->addDay(); }
        
        // Truncar al límite (ahora si es hoy)
        $shiftStart = $shiftStart->min($limitTime);
        $shiftEnd = $shiftEnd->min($limitTime);

        if ($shiftStart->lt($shiftEnd)) {
            $segments[] = ['start' => $shiftStart, 'end' => $shiftEnd, 'type' => 'SHIFT', 'priority' => 1];
        }

        // --- B. Intraday Activities (Incluye Almuerzos/Descansos si son dinámicos) ---
        $intradays = IntradayActivity::where('employee_id', $employeeId)
            ->whereRaw('time_range && tstzrange(?, ?)', [
                $date->copy()->startOfDay()->toIso8601String(),
                $date->copy()->endOfDay()->addDay()->toIso8601String() // Margen para cruce de medianoche
            ])->get();

        foreach ($intradays as $ia) {
            $start = $ia->getRangeStart()?->min($limitTime);
            $end = $ia->getRangeEnd()?->min($limitTime);
            if ($start && $end && $start->lt($end)) {
                $segments[] = ['start' => $start, 'end' => $end, 'type' => 'INTRADAY', 'priority' => 2];
            }
        }

        // --- C. Implied Lunch/Break (Si no están en intraday) ---
        // TODO: En una implementación enterprise, el almuerzo SIEMPRE debería ser un bloque intradía.
        // Si el sistema permite almuerzos "implícitos" (solo texto en el shift), los agregamos aquí con prioridad 2.
        if ($dayInfo->lunch_start_time) {
            $lStart = Carbon::parse($dayInfo->lunch_start_time)->setDate($date->year, $date->month, $date->day)->min($limitTime);
            $lEnd = Carbon::parse($dayInfo->lunch_end_time)->setDate($date->year, $date->month, $date->day)->min($limitTime);
            if ($lEnd->lt($lStart)) { $lEnd->addDay(); }
            if ($lStart->lt($lEnd)) {
                $segments[] = ['start' => $lStart, 'end' => $lEnd, 'type' => 'INTRADAY', 'priority' => 2];
            }
        }

        // --- D. Exceptions ---
        foreach ($dayInfo->exceptions as $exc) {
            $start = Carbon::parse($exc['start_at'])->min($limitTime);
            $end = Carbon::parse($exc['end_at'])->min($limitTime);
            if ($start->lt($end)) {
                $segments[] = ['start' => $start, 'end' => $end, 'type' => 'EXCEPTION', 'priority' => 3];
            }
        }

        return $this->flattenSegments($segments);
    }

    /**
     * Aplica lógica de capas para que los segmentos de mayor prioridad sobrescriban a los de menor.
     */
    private function flattenSegments(array $segments): array
    {
        if (empty($segments)) return [];

        // Ordenar por inicio y luego por prioridad
        usort($segments, fn($a, $b) => $a['start'] <=> $b['start'] ?: $b['priority'] <=> $a['priority']);

        $flattened = [];
        // Esta es una simplificación. Un algoritmo robusto de "interval tree" o "time slicing" es ideal.
        // Para WFM standard: recorremos minuto a minuto o usamos una pila de estados.
        
        // Vamos a usar una aproximación de "puntos de cambio":
        $points = [];
        foreach ($segments as $s) {
            $points[] = $s['start']->getTimestamp();
            $points[] = $s['end']->getTimestamp();
        }
        $points = array_unique($points);
        sort($points);

        for ($i = 0; $i < count($points) - 1; $i++) {
            $start = Carbon::createFromTimestamp($points[$i]);
            $end = Carbon::createFromTimestamp($points[$i+1]);
            
            // Buscar el segmento de mayor prioridad que cubra este intervalo
            $best = null;
            foreach ($segments as $s) {
                if ($s['start']->getTimestamp() <= $points[$i] && $s['end']->getTimestamp() >= $points[$i+1]) {
                    if (!$best || $s['priority'] > $best['priority']) {
                        $best = $s;
                    }
                }
            }

            if ($best) {
                $flattened[] = [
                    'start' => $start,
                    'end' => $end,
                    'type' => $best['type']
                ];
            }
        }

        return $flattened;
    }

    private function getRealTimeline(int $employeeId, CarbonInterface $date): array
    {
        $transitions = AgentStateTransition::where('employee_id', $employeeId)
            ->whereDate('transition_time', $date->toDateString())
            ->orderBy('transition_time')
            ->orderBy('id') // Segundo criterio para consistencia
            ->get();

        $segments = [];
        $count = $transitions->count();

        foreach ($transitions as $i => $t) {
            $start = Carbon::parse($t->transition_time);
            $duration = (int) $t->duration;
            
            // Solo extendemos la ÚLTIMA transición si tiene duración 0 y es hoy
            if ($duration === 0 && $date->isToday() && ($i === $count - 1)) {
                $duration = (int) $start->diffInSeconds(now());
            }

            if ($duration > 0) {
                // Definir prioridades para estados reales (Talking > Ready > Not Ready > others)
                $state = strtoupper(trim((string)$t->agent_state));
                $priority = match($state) {
                    'TALKING' => 10,
                    'RESERVED' => 9,
                    'WORK' => 8,
                    'READY' => 7,
                    'NOT_READY' => 6,
                    default => 1
                };

                $segments[] = [
                    'start' => $start,
                    'end' => $start->copy()->addSeconds($duration),
                    'state' => $state,
                    'priority' => $priority
                ];
            }
        }

        return $this->flattenRealSegments($segments);
    }

    /**
     * Aplica lógica de capas para datos reales en caso de traslapes accidentales.
     */
    private function flattenRealSegments(array $segments): array
    {
        if (empty($segments)) return [];

        $points = [];
        foreach ($segments as $s) {
            $points[] = $s['start']->getTimestamp();
            $points[] = $s['end']->getTimestamp();
        }
        $points = array_unique($points);
        sort($points);

        $flattened = [];
        for ($i = 0; $i < count($points) - 1; $i++) {
            $tsStart = $points[$i];
            $tsEnd = $points[$i+1];
            
            $best = null;
            foreach ($segments as $s) {
                if ($s['start']->getTimestamp() <= $tsStart && $s['end']->getTimestamp() >= $tsEnd) {
                    if (!$best || $s['priority'] > $best['priority']) {
                        $best = $s;
                    }
                }
            }

            if ($best) {
                $flattened[] = [
                    'start' => Carbon::createFromTimestamp($tsStart),
                    'end' => Carbon::createFromTimestamp($tsEnd),
                    'state' => $best['state']
                ];
            }
        }

        return $flattened;
    }
}
