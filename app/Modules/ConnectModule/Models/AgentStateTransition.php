<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentStateTransition extends Model
{
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
