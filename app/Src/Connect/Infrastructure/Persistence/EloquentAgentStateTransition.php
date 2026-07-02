<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentAgentStateTransition extends Model
{
    protected $table = 'agent_state_transitions';

    protected $fillable = [
        'agent_login_id',
        'employee_id',
        'transition_time',
        'agent_state',
        'reason_code',
        'duration',
    ];

    protected $casts = [
        'transition_time' => 'datetime',
        'duration' => 'integer',
        'employee_id' => 'integer',
    ];

    public $timestamps = false;
}
