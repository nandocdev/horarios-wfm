<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueCriteria extends BaseModel
{
    protected $table = 'quality_queue_criteria';

    protected $fillable = [
        'queue_id',
        'criteria_version_id',
        'orden',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class, 'queue_id');
    }

    public function criteriaVersion(): BelongsTo
    {
        return $this->belongsTo(CriteriaVersion::class, 'criteria_version_id');
    }
}
