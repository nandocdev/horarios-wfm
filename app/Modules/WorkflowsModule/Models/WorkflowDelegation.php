<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowDelegation extends Model
{
    protected $fillable = [
        'original_approver_id',
        'delegate_id',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function originalApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'original_approver_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'delegate_id');
    }
}
