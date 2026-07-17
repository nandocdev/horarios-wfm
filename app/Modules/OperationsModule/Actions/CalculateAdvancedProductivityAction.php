<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Actions;

use App\Modules\OperationsModule\Models\AgentDailyMetric;
use App\Shared\Contracts\Employees\EmployeeInterface;
use App\Shared\Contracts\Operations\AgentPerformanceRepositoryInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use App\Shared\Contracts\Telemetry\TelemetryServiceInterface;
use Carbon\CarbonInterface;

final class CalculateAdvancedProductivityAction {
    public function __construct(
        private readonly AgentPerformanceRepositoryInterface $performanceRepo,
        private readonly TelemetryServiceInterface $telemetryService,
        private readonly TelemetryRealtimeRepositoryInterface $realtimeRepo,
    ) {
    }

    public function execute(EmployeeInterface $employee, CarbonInterface $date): AgentDailyMetric {
        // 1. Obtener datos de llamadas
        $calls = $this->performanceRepo->getCallRecords($employee->getId(), $date);

        $callsTotal = $calls->count();
        $talkSeconds = (int) $calls->sum('talk_time');

        // 2. Obtener distribución por cola y AHT Ponderado
        $queueDist = [];
        $weightedAHT = 0;

        if ($callsTotal > 0) {
            $queueGroups = $calls->groupBy('csq_name');
            $queueGoals = $this->realtimeRepo->getQueueAhtGoals()->toArray();

            foreach ($queueGroups as $name => $group) {
                $count = $group->count();
                $dist = $count / $callsTotal;
                $goalAht = (int) ($queueGoals[$name] ?? 300); // Default 5 min si no hay meta

                $queueDist[$name] = [
                    'calls' => $count,
                    'dist' => round($dist, 4),
                    'goal_aht' => $goalAht,
                ];

                $weightedAHT += ($dist * $goalAht);
            }
        }

        // 3. Obtener Tiempos de Telemetría (Capa 2)
        $transitions = $this->telemetryService->getStateTransitions($employee->getId(), $date->copy()->startOfDay(), $date->copy()->endOfDay());

        $loginSeconds = (int) $transitions->sum(fn($t) => $t->metadata['duration'] ?? 0);
        $productiveSeconds = (int) $transitions->filter(fn($t) => $t->metadata['is_productive'] ?? false)
            ->sum(fn($t) => $t->metadata['duration'] ?? 0);

        // 4. Capacidad Teórica (Capa 3)
        $capacityCalls = 0;
        if ($weightedAHT > 0) {
            $capacityCalls = $productiveSeconds / $weightedAHT;
        }

        // 5. Work Units (Capa 4 - Recomendado)
        // WU = Σ (Calls_q * Goal_AHT_q) / 60 (para tenerlo en minutos)
        $workUnits = 0;
        foreach ($queueDist as $q) {
            $workUnits += ($q['calls'] * $q['goal_aht']);
        }
        $workUnitsMinutes = $workUnits / 60;
        $productiveMinutes = $productiveSeconds / 60;

        // 6. KPIs Finales
        $availability = $loginSeconds > 0 ? ($productiveSeconds / $loginSeconds) * 100 : 0;
        $efficiency = $capacityCalls > 0 ? ($callsTotal / $capacityCalls) * 100 : 0;

        // El PWI consolidado
        $pwi = ($availability / 100) * ($efficiency / 100) * 100;

        // Si usamos el modelo de Work Units para la productividad final:
        $productivityWU = $productiveMinutes > 0 ? ($workUnitsMinutes / $productiveMinutes) * 100 : 0;

        return new AgentDailyMetric([
            'employee_id' => $employee->getId(),
            'metric_date' => $date->toDateString(),
            'login_seconds' => $loginSeconds,
            'productive_seconds' => $productiveSeconds,
            'calls_total' => $callsTotal,
            'talk_seconds' => $talkSeconds,
            'weighted_aht' => round($weightedAHT, 2),
            'capacity_calls' => round($capacityCalls, 2),
            'capacity_gap' => round(max(0, $capacityCalls - $callsTotal), 2),
            'work_units' => round($workUnitsMinutes, 2),
            'availability_pct' => round($availability, 2),
            'efficiency_pct' => round($efficiency, 2), // Basado en capacidad de llamadas
            'pwi_pct' => round($pwi, 2),
            'queue_distribution' => $queueDist,
        ]);
    }
}
