<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentCallRecord extends Model
{
    protected $table = 'call_records';

    protected $fillable = [
        'cisco_call_id',
        'queue_id',
        'phone_number',
        'ivr_started_at',
        'ivr_ended_at',
        'talk_time',
        'ring_time',
        'work_time',
        'queue_time',
        'contact_disposition',
        'employee_id',
        'raw_agent_name',
        'citizen_identifier',
        'case_subtype_id',
        'description',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'ivr_started_at' => 'datetime',
        'ivr_ended_at' => 'datetime',
        'closed_at' => 'datetime',
        'employee_id' => 'integer',
        'queue_id' => 'integer',
        'case_subtype_id' => 'integer',
        'talk_time' => 'integer',
        'ring_time' => 'integer',
        'work_time' => 'integer',
        'queue_time' => 'integer',
        'contact_disposition' => 'integer',
    ];
}
