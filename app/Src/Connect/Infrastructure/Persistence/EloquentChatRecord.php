<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentChatRecord extends Model
{
    protected $table = 'chat_records';

    protected $fillable = [
        'conversation_id',
        'agent_login_id',
        'employee_id',
        'start_time',
        'end_time',
        'accepted_at',
        'total_duration',
        'talk_time',
        'author_identifier',
        'destination_identifier',
        'chat_type',
        'chat_source',
        'chat_rating',
        'raw_agent_name',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'accepted_at' => 'datetime',
        'total_duration' => 'integer',
        'talk_time' => 'integer',
        'employee_id' => 'integer',
    ];
}
