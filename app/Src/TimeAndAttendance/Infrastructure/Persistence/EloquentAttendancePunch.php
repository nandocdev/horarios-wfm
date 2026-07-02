<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentAttendancePunch extends Model
{
    protected $table = 'attendance_punches';

    protected $fillable = ['employee_id', 'type', 'punched_at', 'source', 'external_id'];

    protected $casts = [
        'punched_at' => 'datetime',
    ];
}
