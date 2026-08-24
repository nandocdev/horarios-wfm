<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use Illuminate\Database\Eloquent\Model;

class ShrinkageCategory extends Model
{
    protected $table = 'shrinkage_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_paid',
        'is_planned',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'is_planned' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePlanned($query)
    {
        return $query->where('is_planned', true);
    }
}
