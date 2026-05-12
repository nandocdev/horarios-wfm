<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestApproval extends Model
{
    protected $fillable = ['leave_request_id', 'approver_id', 'status', 'comment', 'step_order'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }
}
