<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Alerts\Evaluators;

use App\Modules\OperationsModule\Alerts\Models\AlertRule;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Contracts\Telemetry\TelemetryServiceInterface;
use App\Shared\Contracts\WfmModule\ExpectedAgentStateInterface;

class UnexpectedLogoutEvaluator extends BaseAlertEvaluator
{
    public function eventType(): string
    {
        return 'agent.unexpected_logout';
    }

    public function evaluate(AlertRule $rule): void
    {
        $telemetryService = app(TelemetryServiceInterface::class);
        $expectedState = app(ExpectedAgentStateInterface::class);

        $employees = Employee::where('is_active', true)
            ->whereHas('user')
            ->whereHas('team')
            ->get();

        $employeeIds = $employees->pluck('id')->toArray();
        $realtimeStates = $telemetryService->getBatchCurrentStates($employeeIds);
        $expectedStates = $expectedState->executeBatch($employeeIds);

        foreach ($employees as $employee) {
            $real = $realtimeStates[$employee->id] ?? null;
            $expected = $expectedStates[$employee->id] ?? ['type' => 'OFF'];

            $isExpectedActive = in_array($expected['type'], ['SHIFT', 'INTRADAY']);

            if (! $isExpectedActive) {
                continue;
            }

            $currentState = strtoupper($real?->current_state ?? 'OFFLINE');

            if ($currentState !== 'LOGOUT') {
                $this->resolve($rule, (string) $employee->id);

                continue;
            }

            $lastChange = $real?->last_changed_at;
            $duration = $lastChange ? now()->diffInSeconds($lastChange) : 0;

            if ($duration < ($rule->threshold_seconds ?? 60)) {
                continue;
            }

            if ($this->shouldSuppress($rule, (string) $employee->id, $duration)) {
                continue;
            }

            $this->trigger($rule, [
                'employee_id' => $employee->id,
                'message' => "{$employee->full_name} se desconectó inesperadamente durante su turno.",
                'level' => 'critical',
                'source' => 'unexpected_logout_evaluator',
                'summary' => 'El agente cerró sesión durante su horario laboral sin registro de permiso.',
                'actionUrl' => '/operations/realtime',
                'facts' => [
                    ['label' => 'Empleado', 'value' => $employee->full_name],
                    ['label' => 'Duración', 'value' => gmdate('H:i:s', $duration)],
                    ['label' => 'Último Cambio', 'value' => $lastChange?->format('H:i:s') ?? 'N/A'],
                ],
                'recommendation' => 'Contactar al agente para verificar la causa de la desconexión.',
                'context' => [
                    'employee_id' => $employee->id,
                    'duration_seconds' => $duration,
                ],
            ]);
        }
    }
}
