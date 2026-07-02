<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentAgentState extends Model
{
    protected $table = 'agent_realtime_states';

    protected $fillable = [
        'employee_id', 'external_id', 'current_state',
        'reason_code', 'last_changed_at', 'metadata',
    ];

    protected $casts = [
        'last_changed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public $timestamps = false;
}
