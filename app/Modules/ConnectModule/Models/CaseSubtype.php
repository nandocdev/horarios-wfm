<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Models;

use Illuminate\Database\Eloquent\Model;

class CaseSubtype extends Model {
    protected $table = 'case_subtypes';

    protected $fillable = [
        'code',
        'queue_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function queue() {
        return $this->belongsTo(CallQueue::class, 'queue_id', 'id');
    }

    /**
     * Scope by queue: accepts either queue id or queue name.
     */
    public function scopeByQueue($query, $queue) {
        if (empty($queue)) {
            return $query->where('is_active', true);
        }

        if (is_numeric($queue)) {
            return $query->where('queue_id', (int) $queue)->where('is_active', true);
        }

        // assume queue is name, resolve id
        $queueId = CallQueue::where('name', $queue)->value('id');
        if ($queueId) {
            return $query->where('queue_id', $queueId)->where('is_active', true);
        }

        return $query->whereRaw('1=0');
    }
}
