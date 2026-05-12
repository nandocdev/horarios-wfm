<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyTeamAssignment extends Model
{
    protected $fillable = [
        'weekly_schedule_id', 'team_id', 'day_of_week', 'schedule_id',
        'start_time', 'end_time',
        'lunch_start_time', 'lunch_end_time', 'break_start_time', 'break_end_time',
    ];

    public function weeklySchedule(): BelongsTo
    {
        return $this->belongsTo(WeeklySchedule::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}
