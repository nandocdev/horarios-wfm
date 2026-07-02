<?php

declare(strict_types=1);

namespace App\Src\Wfm\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentWeeklySchedule extends Model
{
    protected $table = 'weekly_schedules';

    protected $fillable = ['week_start_date', 'week_end_date', 'status', 'published_at'];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'published_at' => 'datetime',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(EloquentScheduleAssignment::class, 'weekly_schedule_id');
    }
}
