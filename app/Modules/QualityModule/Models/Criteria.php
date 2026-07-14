<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criteria extends BaseModel
{
    protected $table = 'quality_criteria';

    protected $fillable = [
        'code',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(CriteriaVersion::class, 'criteria_id');
    }

    public function currentVersion(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CriteriaVersion::class, 'criteria_id')
            ->where(function ($query) {
                $query->whereNull('valid_to')
                      ->orWhere('valid_to', '>=', now()->toDateString());
            })
            ->latestOfMany('version');
    }
}
