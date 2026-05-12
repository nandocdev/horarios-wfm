<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledActivityDefinition extends Model
{
    protected $fillable = [
        'name', 'activity_type_id', 'default_duration_minutes',
        'default_location', 'default_instructor', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_duration_minutes' => 'integer',
    ];

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }
}
