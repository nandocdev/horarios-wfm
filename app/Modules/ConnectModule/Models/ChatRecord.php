<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatRecord extends Model
{
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
