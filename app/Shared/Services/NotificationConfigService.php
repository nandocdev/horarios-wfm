<?php

declare(strict_types=1);

namespace App\Shared\Services;

use App\Modules\CoreModule\Models\NotificationConfig;
use App\Shared\Enums\NotificationType;
use App\Shared\Support\Cache\CachePolicyService;
use Illuminate\Support\Facades\Cache;

class NotificationConfigService
{
    public function __construct(
        private readonly CachePolicyService $cachePolicy,
    ) {}

    public function isEnabled(string|NotificationType $eventType): bool
    {
        $config = $this->getConfig($eventType);

        return $config?->is_enabled ?? true;
    }

    public function getChannels(string|NotificationType $eventType): array
    {
        $config = $this->getConfig($eventType);

        return $config?->channels ?? ['database', 'broadcast'];
    }

    public function getConfig(string|NotificationType $eventType): ?NotificationConfig
    {
        $type = $eventType instanceof NotificationType ? $eventType->value : $eventType;

        return $this->cachePolicy->remember('core', 'config', "notification_config:{$type}", function () use ($type) {
            return NotificationConfig::where('event_type', $type)->first();
        });
    }

    public function getAllConfigs(): array
    {
        $dbConfigs = NotificationConfig::orderBy('label')->get()->keyBy('event_type');

        $all = [];
        foreach (NotificationType::cases() as $type) {
            $typeValue = $type->value;
            if (isset($dbConfigs[$typeValue])) {
                $all[] = $dbConfigs[$typeValue];
            } else {
                $all[] = new NotificationConfig([
                    'event_type' => $typeValue,
                    'label' => $this->defaultLabel($type),
                    'description' => $this->defaultDescription($type),
                    'is_enabled' => true,
                    'channels' => ['database', 'broadcast'],
                ]);
            }
        }

        return $all;
    }

    public function upsert(string $eventType, array $data): NotificationConfig
    {
        $this->cachePolicy->flushByPattern('core', 'config');

        return NotificationConfig::updateOrCreate(
            ['event_type' => $eventType],
            $data,
        );
    }

    public function defaultLabel(NotificationType $type): string
    {
        return match ($type) {
            NotificationType::ShiftSwapRequested => 'Intercambio de Turno Solicitado',
            NotificationType::ShiftSwapAccepted => 'Intercambio Aceptado por Compañero',
            NotificationType::ShiftSwapApproved => 'Intercambio Aprobado por WFM',
            NotificationType::ShiftSwapRejected => 'Intercambio Rechazado',
            NotificationType::ShiftSwapCancelled => 'Intercambio Cancelado',
            NotificationType::LeaveRequestCreated => 'Solicitud de Permiso Creada',
            NotificationType::LeaveRequestDecision => 'Respuesta a Solicitud de Permiso',
            NotificationType::SchedulePublished => 'Horario Semanal Publicado',
            NotificationType::ScheduleUpdated => 'Modificación de Horario',
            NotificationType::IntradayActivity => 'Asignación de Actividad Intradía',
            NotificationType::AttendanceIncident => 'Incidencia de Asistencia',
            NotificationType::AdherenceAlert => 'Alerta de Adherencia',
            NotificationType::EvaluationCreated => 'Evaluación de Calidad Realizada',
            NotificationType::SyncFailed => 'Fallo de Sincronización',
        };
    }

    public function defaultDescription(NotificationType $type): string
    {
        return match ($type) {
            NotificationType::ShiftSwapRequested => 'Cuando un agente solicita intercambiar su turno con otro compañero.',
            NotificationType::ShiftSwapAccepted => 'Cuando el compañero acepta el intercambio de turno propuesto.',
            NotificationType::ShiftSwapApproved => 'Cuando el equipo WFM aprueba el intercambio de turno.',
            NotificationType::ShiftSwapRejected => 'Cuando el intercambio de turno es rechazado (por compañero o WFM).',
            NotificationType::ShiftSwapCancelled => 'Cuando el solicitante cancela la solicitud de intercambio.',
            NotificationType::LeaveRequestCreated => 'Cuando un agente crea una solicitud de permiso o vacaciones.',
            NotificationType::LeaveRequestDecision => 'Cuando un supervisor aprueba o rechaza una solicitud de permiso.',
            NotificationType::SchedulePublished => 'Cuando se publica el horario semanal para los agentes.',
            NotificationType::ScheduleUpdated => 'Cuando se modifica una asignación de horario existente.',
            NotificationType::IntradayActivity => 'Cuando se asigna una actividad intradía (break, coaching, capacitación).',
            NotificationType::AttendanceIncident => 'Cuando se registra una incidencia de asistencia (tardanza, ausencia).',
            NotificationType::AdherenceAlert => 'Cuando un agente se desvía de su horario planificado por más de 5 minutos.',
            NotificationType::EvaluationCreated => 'Cuando se completa una evaluación de calidad de una llamada.',
            NotificationType::SyncFailed => 'Cuando falla una sincronización con CUIC, Finesse o UCCX.',
        };
    }
}
