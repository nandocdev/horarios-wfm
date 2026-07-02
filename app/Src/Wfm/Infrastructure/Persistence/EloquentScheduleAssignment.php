<?php

declare(strict_types=1);

namespace App\Src\Wfm\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentScheduleAssignment extends Model
{
    protected $table = 'weekly_schedule_assignments';

    protected $fillable = [
        'weekly_schedule_id', 'employee_id', 'schedule_id',
        'day_of_week', 'start_time', 'end_time',
        'lunch_start_time', 'lunch_end_time',
        'break_start_time', 'break_end_time',
        'swap_request_id', 'is_replaced', 'replaced_at',
    ];

    protected $casts = [
        'is_replaced' => 'boolean',
        'replaced_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'lunch_start_time' => 'datetime',
        'lunch_end_time' => 'datetime',
        'break_start_time' => 'datetime',
        'break_end_time' => 'datetime',
    ];
}
