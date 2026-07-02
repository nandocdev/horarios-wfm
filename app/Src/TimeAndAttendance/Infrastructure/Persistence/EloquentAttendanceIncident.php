<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentAttendanceIncident extends Model
{
    protected $table = 'attendance_incidents';

    protected $fillable = [
        'employee_id', 'incident_type_id', 'incident_date',
        'start_time', 'end_time',
        'status', 'user_comment', 'admin_comment',
        'resolved_by_user_id', 'resolved_at',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'resolved_at' => 'datetime',
    ];
}
