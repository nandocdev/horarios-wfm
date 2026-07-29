<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use App\Modules\CoreModule\Models\User;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForecastVersion extends BaseModel
{
    protected $table = 'forecast_versions';

    protected $fillable = [
        'forecast_group_id',
        'version_number',
        'name',
        'status',
        'generated_by',
        'generated_at',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ForecastGroup::class, 'forecast_group_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scenarios(): HasMany
    {
        return $this->hasMany(ForecastScenario::class, 'forecast_version_id');
    }

    public function intervals(): HasMany
    {
        return $this->hasMany(ForecastInterval::class, 'forecast_version_id');
    }
}
