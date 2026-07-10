<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScheduleException extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'absence_reason_code_id',
        'start_at',
        'end_at',
        'is_full_day',
        'remarks',
        'created_by',
        'metadata',
        'origin_type',
        'origin_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_full_day' => 'boolean',
        'metadata' => 'array',
    ];

    public function origin(): MorphTo
    {
        return $this->morphTo();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(AbsenceReasonCode::class, 'absence_reason_code_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
