<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForecastGroup extends BaseModel
{
    protected $table = 'forecast_groups';

    protected $fillable = [
        'name',
        'group_type',
        'reference_id',
        'description',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ForecastVersion::class, 'forecast_group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
