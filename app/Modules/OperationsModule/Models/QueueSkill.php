<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Models;

use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\PersonnelModule\Models\Skill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueSkill extends Model
{
    protected $table = 'queue_skills';

    protected $fillable = [
        'queue_id',
        'skill_id',
        'priority',
        'minimum_level',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'minimum_level' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(CallQueue::class, 'queue_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
