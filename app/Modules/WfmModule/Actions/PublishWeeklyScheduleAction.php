<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Notifications\SchedulePublishedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PublishWeeklyScheduleAction
{
    /**
     * Publica un horario semanal completo.
     */
    public function execute(int $weeklyScheduleId): WeeklySchedule
    {
        return DB::transaction(function () use ($weeklyScheduleId) {
            $week = WeeklySchedule::findOrFail($weeklyScheduleId);

            if ($week->status !== 'draft') {
                throw new \RuntimeException('Solo se pueden publicar semanas que están en estado borrador.');
            }

            $week->update([
                'status' => 'published',
                'published_at' => Carbon::now(),
            ]);

            // Disparar evento de dominio para que otros módulos reaccionen (ej. Notificaciones)
            \App\Shared\Events\WeeklySchedulePublished::dispatch($week, auth()->id() ?? 0);

            return $week;
        });
    }
}
