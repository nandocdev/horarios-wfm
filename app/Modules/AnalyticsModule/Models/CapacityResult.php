<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapacityResult extends BaseModel
{
    protected $table = 'capacity_results';

    protected $fillable = [
        'capacity_plan_id',
        'queue_id',
        'total_intervals',
        'intervals_with_gap',
        'intervals_with_skill_gap',
        'max_gap',
        'avg_coverage',
        'total_staff_required',
        'total_staff_available',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CapacityPlan::class, 'capacity_plan_id');
    }
}
