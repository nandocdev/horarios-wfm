<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentCallEvent extends Model
{
    protected $table = 'call_events';

    protected $fillable = [
        'external_call_id', 'provider', 'type', 'status',
        'queue_name', 'phone_number', 'citizen_identifier',
        'employee_id', 'agent_login_id',
        'started_at', 'ended_at', 'talk_time',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'talk_time' => 'integer',
        'metadata' => 'array',
    ];
}
