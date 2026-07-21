<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use App\Shared\Models\BaseModel;

class DailyOperatorReport extends BaseModel
{
    protected $fillable = [
        'employee_id',
        'report_date',
        'scheduled_start',
        'scheduled_end',
        'lunch_start',
        'lunch_end',
        'break_start',
        'break_end',
        'talk_seconds',
        'ready_seconds',
        'acw_seconds',
        'reserved_seconds',
        'not_ready_seconds',
        'lunch_seconds',
        'break_seconds',
        'offline_seconds',
        'total_calls',
        'handled_calls',
        'abandoned_calls',
        'total_talk_seconds',
        'total_hold_seconds',
        'total_work_seconds',
        'adherence_pct',
        'occupancy_pct',
        'productivity_pct',
        'avg_handle_time',
        'exception_count',
        'has_exceptions',
        'real_entry',
        'entry_diff_minutes',
        'is_complete',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'lunch_start' => 'datetime',
            'lunch_end' => 'datetime',
            'break_start' => 'datetime',
            'break_end' => 'datetime',
            'talk_seconds' => 'integer',
            'ready_seconds' => 'integer',
            'acw_seconds' => 'integer',
            'reserved_seconds' => 'integer',
            'not_ready_seconds' => 'integer',
            'lunch_seconds' => 'integer',
            'break_seconds' => 'integer',
            'offline_seconds' => 'integer',
            'total_calls' => 'integer',
            'handled_calls' => 'integer',
            'abandoned_calls' => 'integer',
            'total_talk_seconds' => 'integer',
            'total_hold_seconds' => 'integer',
            'total_work_seconds' => 'integer',
            'exception_count' => 'integer',
            'has_exceptions' => 'boolean',
            'is_complete' => 'boolean',
            'entry_diff_minutes' => 'integer',
            'real_entry' => 'datetime',
            'adherence_pct' => 'float',
            'occupancy_pct' => 'float',
            'productivity_pct' => 'float',
            'avg_handle_time' => 'float',
        ];
    }
}
