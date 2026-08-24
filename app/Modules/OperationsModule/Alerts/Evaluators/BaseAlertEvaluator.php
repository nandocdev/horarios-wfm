<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Alerts\Evaluators;

use App\Modules\CoreModule\Models\User;
use App\Modules\OperationsModule\Alerts\Models\AlertEvent;
use App\Modules\OperationsModule\Alerts\Models\AlertRule;
use App\Modules\OperationsModule\Alerts\Notifications\AlertEscalatedNotification;
use App\Modules\OperationsModule\Alerts\Notifications\AlertTriggeredNotification;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\DTOs\NotificationDTO;
use Illuminate\Support\Facades\Log;

abstract class BaseAlertEvaluator
{
    abstract public function eventType(): string;

    abstract public function evaluate(AlertRule $rule): void;

    protected function shouldSuppress(AlertRule $rule, string $employeeId, int|float $thresholdSeconds): bool
    {
        $recent = AlertEvent::where('alert_rule_id', $rule->id)
            ->where('employee_id', $employeeId)
            ->whereNull('resolved_at')
            ->where('last_triggered_at', '>', now()->subMinutes($rule->cooldown_minutes))
            ->first();

        return $recent !== null;
    }

    protected function trigger(AlertRule $rule, array $data): void
    {
        $employeeId = $data['employee_id'] ?? null;
        $queueId = $data['queue_id'] ?? null;
        $message = $data['message'] ?? '';
        $level = $data['level'] ?? 'warning';
        $context = $data['context'] ?? [];
        $source = $data['source'] ?? static::class;

        $now = now();

        $existing = AlertEvent::where('alert_rule_id', $rule->id)
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($queueId, fn ($q) => $q->where('queue_id', $queueId))
            ->whereNull('resolved_at')
            ->first();

        if ($existing) {
            $existing->update([
                'last_triggered_at' => $now,
                'triggered_count' => $existing->triggered_count + 1,
                'message' => $message,
                'context' => $context,
                'level' => $level,
            ]);

            $this->sendNotification($rule, $existing, $data);
            $this->checkEscalation($rule, $existing, $data);

            return;
        }

        $event = AlertEvent::create([
            'alert_rule_id' => $rule->id,
            'employee_id' => $employeeId,
            'queue_id' => $queueId,
            'source' => $source,
            'message' => $message,
            'level' => $level,
            'context' => $context,
            'first_triggered_at' => $now,
            'last_triggered_at' => $now,
            'triggered_count' => 1,
            'expires_at' => $rule->cooldown_minutes ? $now->copy()->addMinutes($rule->cooldown_minutes) : null,
        ]);

        $this->sendNotification($rule, $event, $data);
    }

    protected function sendNotification(AlertRule $rule, AlertEvent $event, array $data): void
    {
        $employee = $event->employee_id ? Employee::find($event->employee_id) : null;
        $manager = $employee?->manager ?? $employee?->team?->supervisor;

        $recipients = [];

        if ($manager?->user) {
            $recipients[] = $manager->user;
        }

        if (! empty($rule->escalation_roles)) {
            $roleUsers = User::role($rule->escalation_roles)->get();
            $recipients = array_merge($recipients, $roleUsers->all());
        }

        $recipients = collect($recipients)->unique('id');

        if ($recipients->isEmpty()) {
            Log::warning('[Alerts] No hay destinatarios para alerta', ['rule' => $rule->event_type]);

            return;
        }

        $dto = new NotificationDTO(
            title: $rule->label,
            message: $data['message'] ?? $event->message,
            summary: $data['summary'] ?? 'Alerta operativa del sistema.',
            actionUrl: $data['actionUrl'] ?? '/operations/realtime',
            icon: $data['icon'] ?? match ($event->level) {
                'critical' => 'exclamation-circle',
                'warning' => 'exclamation-triangle',
                default => 'information-circle',
            },
            level: $event->level,
            notificationType: $rule->event_type,
            facts: $data['facts'] ?? [],
            recommendation: $data['recommendation'] ?? 'Verificar la situación del agente en el monitoreo en tiempo real.',
            resourceType: 'alert',
            resourceId: (string) $event->id,
        );

        foreach ($recipients as $user) {
            $user->notify(new AlertTriggeredNotification($dto));
        }
    }

    protected function checkEscalation(AlertRule $rule, AlertEvent $event, array $data): void
    {
        $escalationMinutes = $rule->escalation_minutes;

        if (empty($escalationMinutes) || empty($rule->escalation_roles)) {
            return;
        }

        $duration = now()->diffInMinutes($event->first_triggered_at);

        foreach ($escalationMinutes as $level => $minutes) {
            $roleName = $rule->escalation_roles[$level] ?? null;

            if (! $roleName) {
                continue;
            }

            if ($duration >= $minutes) {
                $existingEsc = $event->escalations()
                    ->where('escalation_level', $level + 1)
                    ->first();

                if ($existingEsc) {
                    continue;
                }

                $escalation = $event->escalations()->create([
                    'escalation_level' => $level + 1,
                    'escalated_to_role' => $roleName,
                    'escalated_at' => now(),
                ]);

                $roleUsers = User::role($roleName)->get();

                if ($roleUsers->isEmpty()) {
                    continue;
                }

                $dto = new NotificationDTO(
                    title: "[Escalado] {$rule->label}",
                    message: "Alerta escalada a {$roleName}. {$event->message}",
                    summary: "La alerta lleva {$duration} minutos activa sin resolverse.",
                    actionUrl: '/operations/realtime',
                    icon: 'arrow-up-circle',
                    level: 'critical',
                    notificationType: "{$rule->event_type}.escalated",
                    facts: array_merge($data['facts'] ?? [], [
                        ['label' => 'Nivel de Escalamiento', 'value' => (string) ($level + 1)],
                        ['label' => 'Rol', 'value' => $roleName],
                        ['label' => 'Duración', 'value' => $duration.' min'],
                    ]),
                    recommendation: 'Se requiere intervención inmediata.',
                    resourceType: 'alert',
                    resourceId: $event->id,
                );

                foreach ($roleUsers as $user) {
                    $user->notify(new AlertEscalatedNotification($dto));
                }

                $escalation->update(['notified_at' => now()]);
            }
        }
    }

    protected function resolve(AlertRule $rule, string $employeeId): void
    {
        AlertEvent::where('alert_rule_id', $rule->id)
            ->where('employee_id', $employeeId)
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);
    }
}
