<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Modules\CommunicationsModule\Notifications\WeeklySchedulePublishedNotification;
use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Shared\Events\WeeklySchedulePublished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendWeeklySchedulePublishedNotification implements ShouldQueue
{
    /**
     * Maneja el envío de notificaciones cuando se publica un horario semanal.
     *
     * Reglas de negocio:
     * - Se notifica a todos los Agentes (rol: operator).
     * - Se notifica a Coordinadores y Supervisores.
     * - Se notifica a los Jefes (roles: chief, director, admin, wfm).
     */
    public function handle(WeeklySchedulePublished $event): void
    {
        $weekly = $event->weeklySchedule;

        if (! $weekly instanceof WeeklySchedule) {
            Log::error('El evento WeeklySchedulePublished no contiene una instancia válida de WeeklySchedule.');

            return;
        }

        $payload = [
            'type' => 'weekly_schedule.published',
            'weekly_schedule_id' => $weekly->id ?? null,
            'published_by' => $event->publishedByUserId,
            'week_period' => $weekly->week_start_date->format('d/m/Y').' al '.$weekly->week_end_date->format('d/m/Y'),
            'action_url' => route('schedules.my-schedule', ['week' => $weekly->id]),
            'title' => 'Nuevo Horario Publicado',
            'message' => "Se ha publicado el horario oficial para la semana del {$weekly->week_start_date->format('d/m/Y')}.",
            'level' => 'success',
        ];

        try {
            // Obtener todos los destinatarios según roles de forma única
            $recipients = User::role([
                'operator',    // Agentes
                'coordinator', // Coordinadores
                'supervisor',  // Supervisores
                'chief',       // Jefes
                'director',    // Directores
                'admin',       // Administradores
                'wfm',          // WFM Managers
            ])
                ->active()
                ->distinct()
                ->get();

            if ($recipients->isEmpty()) {
                Log::warning('No se encontraron destinatarios para la notificación de horario publicado.');

                return;
            }

            foreach ($recipients as $user) {
                try {
                    $user->notify(new WeeklySchedulePublishedNotification($payload));
                } catch (\Throwable $e) {
                    Log::warning("Error al notificar al usuario {$user->id}: ".$e->getMessage());
                    // Continuamos con el siguiente usuario
                }
            }

        } catch (\Throwable $e) {
            Log::error('Error al enviar notificaciones de horario publicado: '.$e->getMessage());
            throw $e;
        }
    }
}
