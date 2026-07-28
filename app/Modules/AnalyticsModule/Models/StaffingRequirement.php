<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use App\Shared\Models\BaseModel;

class StaffingRequirement extends BaseModel
{
    protected $table = 'staffing_requirements';

    protected $fillable = [
        'interval_start',
        'interval_end',
        'interval_minutes',
        'queue_id',
        'required_agents',
        'scheduled_agents',
        'available_agents',
        'coverage',
        'gap',
        'shrinkage_rate',
        'forecast_version_id',
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
}
