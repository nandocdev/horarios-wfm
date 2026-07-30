<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Alerts\Models;

use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertEvent extends BaseModel
{
    protected $table = 'alert_events';

    protected $fillable = [
        'alert_rule_id',
        'employee_id',
        'queue_id',
        'source',
        'message',
        'level',
        'context',
        'first_triggered_at',
        'last_triggered_at',
        'triggered_count',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'first_triggered_at' => 'datetime',
            'last_triggered_at' => 'datetime',
            'triggered_count' => 'integer',
            'is_acknowledged' => 'boolean',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'alert_rule_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(AlertEscalation::class, 'alert_event_id');
    }
}
