<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentDailyMetric extends BaseModel
{
    protected $fillable = [
        'employee_id',
        'metric_date',
        'login_seconds',
        'productive_seconds',
        'calls_total',
        'handled_calls',
        'talk_seconds',
        'work_seconds',
        'hold_seconds',
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
        'handled_calls' => 'integer',
        'work_seconds' => 'integer',
        'hold_seconds' => 'integer',
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
