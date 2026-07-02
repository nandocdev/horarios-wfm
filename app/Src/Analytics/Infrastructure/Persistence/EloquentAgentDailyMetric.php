<?php

declare(strict_types=1);

namespace App\Src\Analytics\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentAgentDailyMetric extends Model
{
    protected $table = 'agent_daily_metrics';

    protected $fillable = [
        'employee_id', 'metric_date',
        'login_seconds', 'productive_seconds',
        'calls_total', 'talk_seconds',
        'weighted_aht', 'capacity_calls', 'capacity_gap',
        'work_units', 'availability_pct', 'efficiency_pct',
        'pwi_pct', 'queue_distribution',
        'adherence_pct', 'productivity_pct', 'utilization_pct',
        'occupancy_pct', 'scheduled_seconds', 'adherent_seconds',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'login_seconds' => 'integer',
        'productive_seconds' => 'integer',
        'calls_total' => 'integer',
        'talk_seconds' => 'integer',
        'weighted_aht' => 'float',
        'capacity_calls' => 'float',
        'capacity_gap' => 'float',
        'work_units' => 'float',
        'availability_pct' => 'float',
        'efficiency_pct' => 'float',
        'pwi_pct' => 'float',
        'queue_distribution' => 'array',
        'adherence_pct' => 'float',
        'productivity_pct' => 'float',
        'utilization_pct' => 'float',
        'occupancy_pct' => 'float',
        'scheduled_seconds' => 'integer',
        'adherent_seconds' => 'integer',
    ];
}
