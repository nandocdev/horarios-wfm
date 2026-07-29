<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapacityInterval extends BaseModel
{
    protected $table = 'capacity_intervals';

    protected $fillable = [
        'capacity_plan_id',
        'interval_start',
        'interval_end',
        'interval_minutes',
        'queue_id',
        'forecast_call_volume',
        'forecast_aht',
        'staff_required',
        'staff_scheduled',
        'staff_available',
        'staff_with_skill',
        'coverage',
        'gap',
        'skill_gap',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'interval_start' => 'datetime',
            'interval_end' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CapacityPlan::class, 'capacity_plan_id');
    }
}
