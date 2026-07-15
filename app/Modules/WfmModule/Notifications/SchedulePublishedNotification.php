<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Notifications;

use App\Modules\WfmModule\Models\WeeklySchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SchedulePublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected WeeklySchedule $weeklySchedule
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'schedule_published',
            'weekly_schedule_id' => $this->weeklySchedule->id,
            'start_date' => $this->weeklySchedule->week_start_date->format('Y-m-d'),
            'title' => 'Nuevo Horario Publicado',
            'message' => "Se ha publicado el horario para la semana del {$this->weeklySchedule->week_start_date->format('d/m/Y')}.",
            'action_url' => route('schedules.my-schedule', [], false),
        ];
    }
}
