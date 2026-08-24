<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Alerts\Evaluators;

use App\Modules\OperationsModule\Alerts\Models\AlertRule;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LunchExceededEvaluator extends BaseAlertEvaluator
{
    public function eventType(): string
    {
        return 'agent.lunch_exceeded';
    }

    public function evaluate(AlertRule $rule): void
    {
        $realtimeRepo = app(TelemetryRealtimeRepositoryInterface::class);

        $employees = Cache::remember('active_employees_with_user', 300, function () {
            return Employee::query()
                ->where('is_active', true)
                ->whereHas('user')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn ($e) => ['id' => $e->id, 'full_name' => trim("{$e->first_name} {$e->last_name}")])
                ->values()
                ->toArray();
        });

        foreach ($employees as $employee) {
            $states = $realtimeRepo->getRealtimeStates([$employee['id']]);
            $current = $states->first();

            if (! $current) {
                continue;
            }

            $state = strtoupper($current->current_state ?? '');
            $reason = strtoupper((string) ($current->reason_code ?? ''));

            $isLunch = $state === 'NOT_READY' && (
                str_contains($reason, 'LUNCH') ||
                str_contains($reason, 'ALMUERZO') ||
                str_contains($reason, 'COMIDA')
            );

            if (! $isLunch) {
                $this->resolve($rule, (string) $employee['id']);

                continue;
            }

            $lastChange = $current->last_changed_at ? Carbon::parse($current->last_changed_at) : null;
            $duration = $lastChange ? now()->diffInSeconds($lastChange) : 0;

            if ($duration < ($rule->threshold_seconds ?? 3600)) {
                continue;
            }

            if ($this->shouldSuppress($rule, (string) $employee['id'], $duration)) {
                continue;
            }

            $this->trigger($rule, [
                'employee_id' => $employee['id'],
                'message' => "{$employee['full_name']} lleva {$duration}s en almuerzo.",
                'level' => 'warning',
                'source' => 'lunch_exceeded_evaluator',
                'summary' => 'El agente ha excedido el tiempo de almuerzo permitido.',
                'actionUrl' => '/operations/realtime',
                'facts' => [
                    ['label' => 'Empleado', 'value' => $employee['full_name']],
                    ['label' => 'Duración', 'value' => gmdate('H:i:s', $duration)],
                    ['label' => 'Estado', 'value' => $state],
                ],
                'recommendation' => 'Verificar si el agente requiere más tiempo o si hubo un error de sincronización.',
                'context' => [
                    'employee_id' => $employee['id'],
                    'duration_seconds' => $duration,
                    'reason_code' => $reason,
                ],
            ]);
        }
    }
}
