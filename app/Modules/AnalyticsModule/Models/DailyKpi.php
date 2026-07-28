<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use App\Shared\Models\BaseModel;

class DailyKpi extends BaseModel
{
    protected $table = 'daily_kpis';

    protected $fillable = [
        'evaluation_date',
        'granularity',
        'dim_employee_id',
        'dim_team_id',
        'dim_queue_id',
        'occupancy',
        'utilization',
        'productivity',
        'conformance',
        'adherence',
        'aht_seconds',
        'acw_seconds',
        'asa_seconds',
        'service_level',
        'shrinkage_pct',
        'forecast_accuracy_pct',
        'quality_score',
        'total_calls',
        'total_talk_seconds',
        'total_hold_seconds',
        'total_wrap_seconds',
        'total_ready_seconds',
        'total_not_ready_seconds',
        'total_login_seconds',
        'total_scheduled_minutes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'evaluation_date' => 'date',
            'metadata' => 'array',
        ];
    }
}
