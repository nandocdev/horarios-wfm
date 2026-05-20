<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftSwapRequest extends Model
{
    protected $fillable = [
        'requester_id',
        'recipient_id',
        'requested_date',
        'status',
        'reason',
        'requester_assignment_snapshot',
        'recipient_assignment_snapshot',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'requester_assignment_snapshot' => 'array',
        'recipient_assignment_snapshot' => 'array',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recipient_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ShiftSwapApproval::class);
    }
}
