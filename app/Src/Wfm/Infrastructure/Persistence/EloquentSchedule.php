<?php

declare(strict_types=1);

namespace App\Src\Wfm\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentSchedule extends Model
{
    protected $table = 'schedules';

    protected $fillable = [
        'name', 'start_time', 'end_time', 'total_minutes', 'break_minutes', 'lunch_minutes',
        'is_lunch_paid', 'is_break_paid', 'is_active', 'allowed_days',
    ];

    protected $casts = [
        'is_lunch_paid' => 'boolean',
        'is_break_paid' => 'boolean',
        'is_active' => 'boolean',
        'total_minutes' => 'integer',
        'break_minutes' => 'integer',
        'lunch_minutes' => 'integer',
        'allowed_days' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}
