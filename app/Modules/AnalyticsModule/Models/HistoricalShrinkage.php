<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalShrinkage extends Model
{
    protected $table = 'historical_shrinkage';

    protected $fillable = [
        'employee_id',
        'shrinkage_category_id',
        'date',
        'interval_start',
        'interval_end',
        'duration_minutes',
        'source_type',
        'source_id',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'interval_start' => 'datetime',
            'interval_end' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShrinkageCategory::class, 'shrinkage_category_id');
    }
}
