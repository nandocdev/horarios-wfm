<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WorkflowsModule\Models\ShiftSwapRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyScheduleAssignment extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('active', function ($builder) {
            $builder->where('is_replaced', false);
        });
    }

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

    public function swapRequest(): BelongsTo
    {
        return $this->belongsTo(ShiftSwapRequest::class, 'swap_request_id');
    }

    public function weeklySchedule(): BelongsTo
    {
        return $this->belongsTo(WeeklySchedule::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Obtiene la fecha real de la asignación basada en el inicio de la semana y el día de la semana.
     */
    public function getDateAttribute(): mixed
    {
        return $this->weeklySchedule->week_start_date->copy()->addDays($this->day_of_week - 1);
    }
}
