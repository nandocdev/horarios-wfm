<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Shared\Events\WeeklySchedulePublished;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            WeeklySchedulePublished::dispatch($week, auth()->id() ?? 0);

            return $week;
        });
    }
}
