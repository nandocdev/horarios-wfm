<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentIntervalMetric extends BaseModel
{
    protected $table = 'agent_interval_metrics';

    protected $fillable = [
        'employee_id',
        'interval_start',
        'interval_end',
        'talk_seconds',
        'hold_seconds',
        'ready_seconds',
        'not_ready_seconds',
        'wrap_seconds',
        'calls_handled',
        'aht_seconds',
        'occupancy',
        'utilization',
        'adherence',
        'queue_distribution',
    ];

    protected function casts(): array
    {
        return [
            'interval_start' => 'datetime',
            'interval_end' => 'datetime',
            'queue_distribution' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
