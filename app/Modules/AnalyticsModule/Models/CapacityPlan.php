<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use App\Modules\CoreModule\Models\User;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapacityPlan extends BaseModel
{
    protected $table = 'capacity_plans';

    protected $fillable = [
        'name',
        'description',
        'status',
        'plan_date',
        'generated_by',
        'generated_at',
        'forecast_version_id',
        'shrinkage_rate',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'plan_date' => 'date',
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function intervals(): HasMany
    {
        return $this->hasMany(CapacityInterval::class, 'capacity_plan_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(CapacityResult::class, 'capacity_plan_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
