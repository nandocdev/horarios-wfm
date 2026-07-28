<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use App\Shared\Models\BaseModel;

class ForecastAccuracy extends BaseModel
{
    protected $table = 'forecast_accuracy';

    protected $fillable = [
        'forecast_version_id',
        'forecast_scenario_id',
        'queue_id',
        'evaluation_date',
        'forecast_call_volume',
        'actual_call_volume',
        'forecast_aht',
        'actual_aht',
        'volume_error',
        'volume_abs_error',
        'volume_ape',
        'mape',
        'bias',
        'rmse',
        'accuracy',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'evaluation_date' => 'date',
            'metadata' => 'array',
        ];
    }
}
