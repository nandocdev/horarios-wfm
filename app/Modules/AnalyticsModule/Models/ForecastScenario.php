<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForecastScenario extends BaseModel
{
    protected $table = 'forecast_scenarios';

    protected $fillable = [
        'forecast_version_id',
        'name',
        'scenario_type',
        'multiplier',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'multiplier' => 'decimal:2',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ForecastVersion::class, 'forecast_version_id');
    }

    public function intervals(): HasMany
    {
        return $this->hasMany(ForecastInterval::class, 'forecast_scenario_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
