<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCallPerformance extends Model {
    protected $table = 'agent_call_performance';

    protected $fillable = [
        'agent_login_id',
        'employee_id',
        'agent_ext',
        'start_time',
        'end_time',
        'total_duration',
        'talk_time',
        'hold_time',
        'work_time',
        'phone_number',
        'ani',
        'csq_name',
        'call_skill',
        'call_type',
        'raw_agent_name',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'total_duration' => 'integer',
        'talk_time' => 'integer',
        'hold_time' => 'integer',
        'work_time' => 'integer',
        'employee_id' => 'integer',
    ];

    public function employee(): BelongsTo {
        return $this->belongsTo(Employee::class);
    }
}
