<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceIncident extends Model
{
    protected $fillable = [
        'employee_id', 'incident_type_id', 'incident_date',
        'start_time', 'end_time', 'user_comment', 'admin_comment',
    ];

    protected $casts = [
        'incident_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(IncidentType::class, 'incident_type_id');
    }
}
