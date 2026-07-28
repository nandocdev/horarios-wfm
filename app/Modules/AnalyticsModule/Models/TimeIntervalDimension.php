<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use Illuminate\Database\Eloquent\Model;

class TimeIntervalDimension extends Model
{
    protected $table = 'analytics_time_interval_dimension';

    protected $fillable = [
        'interval_key',
        'start_time',
        'end_time',
        'interval_minutes',
        'slot_number',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'string',
            'end_time' => 'string',
        ];
    }
}
