<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Alerts\Evaluators;

use App\Modules\OperationsModule\Alerts\Models\AlertRule;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryServiceInterface;
use Illuminate\Support\Facades\Cache;

class NoLoginEvaluator extends BaseAlertEvaluator
{
    public function eventType(): string
    {
        return 'agent.no_login';
    }

    public function evaluate(AlertRule $rule): void
    {
        $telemetryService = app(TelemetryServiceInterface::class);
        $scheduleQueries = app(DashboardScheduleQueriesInterface::class);

        $today = now()->toDateString();
        $dayOfWeek = now()->dayOfWeekIso;

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
        $assignments = $scheduleQueries->getScheduledAssignmentsWithSchedule($employeeIds, $today, $dayOfWeek);

        foreach ($employees as $employee) {
            $assignment = $assignments->firstWhere('employee_id', $employee['id']);

            if (! $assignment || ! $assignment->start_time) {
                continue;
            }

            $shiftStart = $assignment->start_time;
            $graceEnd = $shiftStart->copy()->addSeconds($rule->threshold_seconds ?? 300);

            if (now()->lessThan($graceEnd)) {
                continue;
            }

            $real = $realtimeStates[$employee['id']] ?? null;
            $currentState = strtoupper($real?->current_state ?? 'UNKNOWN');

            if (! in_array($currentState, ['LOGOUT', 'OFFLINE', 'UNKNOWN'], true)) {
                $this->resolve($rule, (string) $employee['id']);

                continue;
            }

            $duration = (int) now()->diffInSeconds($shiftStart);

            if ($this->shouldSuppress($rule, (string) $employee['id'], $duration)) {
                continue;
            }

            $this->trigger($rule, [
                'employee_id' => $employee['id'],
                'message' => "{$employee['full_name']} no ha iniciado sesión. Turno: {$assignment->start_time->format('H:i')}.",
                'level' => 'critical',
                'source' => 'no_login_evaluator',
                'summary' => 'El agente no se ha logueado a la hora de inicio de su turno.',
                'actionUrl' => '/operations/realtime',
                'facts' => [
                    ['label' => 'Empleado', 'value' => $employee['full_name']],
                    ['label' => 'Entrada Programada', 'value' => $assignment->start_time->format('H:i')],
                    ['label' => 'Retraso', 'value' => gmdate('H:i:s', $duration)],
                ],
                'recommendation' => 'Contactar al agente para verificar la razón del retraso.',
                'context' => [
                    'employee_id' => $employee['id'],
                    'duration_seconds' => $duration,
                    'scheduled_start' => $assignment->start_time,
                ],
            ]);
        }
    }
}
