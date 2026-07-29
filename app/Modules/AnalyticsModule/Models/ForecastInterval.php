<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForecastInterval extends BaseModel
{
    protected $table = 'forecast_intervals';

    protected $fillable = [
        'forecast_scenario_id',
        'interval_start',
        'interval_end',
        'interval_minutes',
        'call_volume_forecast',
        'talk_time_seconds_forecast',
        'aht_seconds_forecast',
        'staff_required',
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

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(ForecastScenario::class, 'forecast_scenario_id');
    }
}
