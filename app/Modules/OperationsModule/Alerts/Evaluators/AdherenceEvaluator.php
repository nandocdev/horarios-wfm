<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Alerts\Evaluators;

use App\Modules\OperationsModule\Alerts\Models\AlertRule;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Contracts\Telemetry\TelemetryServiceInterface;
use App\Shared\Contracts\WfmModule\ExpectedAgentStateInterface;
use Illuminate\Support\Facades\Cache;

class AdherenceEvaluator extends BaseAlertEvaluator
{
    public function eventType(): string
    {
        return 'adherence.alert';
    }

    public function evaluate(AlertRule $rule): void
    {
        $telemetryService = app(TelemetryServiceInterface::class);
        $expectedState = app(ExpectedAgentStateInterface::class);

        $employees = Cache::remember('active_employees_with_team', 300, function () {
            return Employee::query()
                ->where('is_active', true)
                ->whereHas('user')
                ->whereHas('team')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn ($e) => ['id' => $e->id, 'full_name' => trim("{$e->first_name} {$e->last_name}")])
                ->values()
                ->toArray();
        });

        $employeeIds = array_column($employees, 'id');
        $realtimeStates = $telemetryService->getBatchCurrentStates($employeeIds);
        $expectedStates = $expectedState->executeBatch($employeeIds);

        foreach ($employees as $employee) {
            $real = $realtimeStates[$employee['id']] ?? null;
            $expected = $expectedStates[$employee['id']] ?? ['type' => 'OFF'];

            $isExpectedActive = in_array($expected['type'], ['SHIFT', 'INTRADAY']);

            if (! $isExpectedActive) {
                continue;
            }

            $currentState = strtoupper($real?->current_state ?? 'OFFLINE');
            $isLogoutOrOffline = in_array($currentState, ['OFFLINE', 'LOGOUT', 'LOGGED_OUT', 'UNKNOWN']);

            if (! $isLogoutOrOffline) {
                $this->resolve($rule, (string) $employee['id']);

                continue;
            }

            $lastChange = $real?->last_changed_at;
            $duration = $lastChange ? now()->diffInSeconds($lastChange) : 0;

            if ($duration < ($rule->threshold_seconds ?? 300)) {
                continue;
            }

            if ($this->shouldSuppress($rule, (string) $employee['id'], $duration)) {
                continue;
            }

            $this->trigger($rule, [
                'employee_id' => $employee['id'],
                'message' => "{$employee['full_name']} — fuera de adherencia ({$duration}s).",
                'level' => 'critical',
                'source' => 'adherence_evaluator',
                'summary' => 'Se detectó una desviación respecto al horario planificado.',
                'icon' => 'exclamation-triangle',
                'actionUrl' => '/operations/realtime',
                'facts' => [
                    ['label' => 'Empleado', 'value' => $employee['full_name']],
                    ['label' => 'Estado Esperado', 'value' => $expected['label'] ?? 'Activo'],
                    ['label' => 'Duración', 'value' => gmdate('H:i:s', $duration)],
                ],
                'recommendation' => 'Verificar la situación del agente en tiempo real.',
                'context' => [
                    'employee_id' => $employee['id'],
                    'duration_seconds' => $duration,
                    'expected_type' => $expected['type'],
                ],
            ]);
        }
    }
}
