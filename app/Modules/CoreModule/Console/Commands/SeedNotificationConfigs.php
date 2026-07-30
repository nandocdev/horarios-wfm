<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Console\Commands;

use App\Modules\CoreModule\Models\NotificationConfig;
use App\Shared\Enums\NotificationType;
use Illuminate\Console\Command;

final class SeedNotificationConfigs extends Command
{
    protected $signature = 'notifications:seed-configs';

    protected $description = 'Crea o actualiza las configuraciones predeterminadas de notificaciones';

    public function handle(): int
    {
        $this->info('Sembrando configuraciones de notificaciones...');

        $defaults = [
            NotificationType::ShiftSwapRequested->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'swap_recipient',
            ],
            NotificationType::ShiftSwapAccepted->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'swap_requester',
            ],
            NotificationType::ShiftSwapApproved->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'both_parties',
            ],
            NotificationType::ShiftSwapRejected->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'both_parties',
            ],
            NotificationType::ShiftSwapCancelled->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'swap_recipient',
            ],
            NotificationType::LeaveRequestCreated->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'manager',
            ],
            NotificationType::LeaveRequestDecision->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'employee',
            ],
            NotificationType::SchedulePublished->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'all_agents',
            ],
            NotificationType::ScheduleUpdated->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'affected_employee',
            ],
            NotificationType::IntradayActivity->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'affected_employee',
            ],
            NotificationType::AttendanceIncident->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'employee',
            ],
            NotificationType::AdherenceAlert->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'manager',
            ],
            NotificationType::EvaluationCreated->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'employee',
            ],
            NotificationType::SyncFailed->value => [
                'channels' => ['database', 'broadcast'],
                'recipient_type' => 'specific_users',
            ],
        ];

        $count = 0;

        foreach (NotificationType::cases() as $type) {
            $config = $defaults[$type->value] ?? [];
            $label = match ($type) {
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

            NotificationConfig::updateOrCreate(
                ['event_type' => $type->value],
                [
                    'label' => $label,
                    'channels' => $config['channels'] ?? ['database', 'broadcast'],
                    'recipient_type' => $config['recipient_type'] ?? null,
                    'is_enabled' => true,
                ],
            );

            $count++;
        }

        $this->info("{$count} configuraciones de notificación creadas/actualizadas.");

        return self::SUCCESS;
    }
}
