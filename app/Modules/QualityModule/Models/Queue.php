<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Queue extends BaseModel
{
    protected $table = 'quality_queues';

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function queueCriteria(): HasMany
    {
        return $this->hasMany(QueueCriteria::class, 'queue_id');
    }
}
