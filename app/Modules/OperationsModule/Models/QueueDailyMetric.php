<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Models;

use App\Modules\ConnectModule\Models\CallQueue;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueDailyMetric extends BaseModel
{
    protected $fillable = [
        'queue_id',
        'metric_date',
        'offered_calls',
        'handled_calls',
        'abandoned_calls',
        'sl_calls',
        'total_talk_seconds',
        'total_work_seconds',
        'total_hold_seconds',
        'total_wait_seconds',
        'max_wait_seconds',
        'min_wait_seconds',
        'total_abandon_seconds',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'offered_calls' => 'integer',
        'handled_calls' => 'integer',
        'abandoned_calls' => 'integer',
        'sl_calls' => 'integer',
        'total_talk_seconds' => 'integer',
        'total_work_seconds' => 'integer',
        'total_hold_seconds' => 'integer',
        'total_wait_seconds' => 'integer',
        'max_wait_seconds' => 'integer',
        'min_wait_seconds' => 'integer',
        'total_abandon_seconds' => 'integer',
    ];

    public function queue(): BelongsTo
    {
        return $this->belongsTo(CallQueue::class, 'queue_id');
    }
}
