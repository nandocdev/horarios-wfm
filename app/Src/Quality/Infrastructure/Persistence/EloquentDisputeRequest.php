<?php

declare(strict_types=1);

namespace App\Src\Quality\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentDisputeRequest extends Model
{
    protected $table = 'quality_dispute_requests';

    protected $fillable = [
        'evaluation_id', 'raised_by_agent_id', 'reason',
        'status', 'resolution_comment', 'resolved_by_user_id', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];
}
