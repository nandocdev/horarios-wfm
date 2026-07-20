<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'requestable_type',
        'requestable_id',
        'requester_id',
        'type',
        'status',
        'data',
        'reason',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForApprover($query, int $employeeId)
    {
        return $query->whereHas('approvals', fn ($q) => $q
            ->where('approver_id', $employeeId)
            ->where('status', 'pending')
        );
    }
}
