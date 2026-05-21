<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentDailyMetric extends Model
{
    protected $fillable = [
        'employee_id',
        'metric_date',
        'login_seconds',
        'productive_seconds',
        'calls_total',
        'talk_seconds',
        'weighted_aht',
        'capacity_calls',
        'capacity_gap',
        'work_units',
        'availability_pct',
        'efficiency_pct',
        'pwi_pct',
        'queue_distribution',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'queue_distribution' => 'array',
        'weighted_aht' => 'float',
        'capacity_calls' => 'float',
        'capacity_gap' => 'float',
        'work_units' => 'float',
        'availability_pct' => 'float',
        'efficiency_pct' => 'float',
        'pwi_pct' => 'float',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
