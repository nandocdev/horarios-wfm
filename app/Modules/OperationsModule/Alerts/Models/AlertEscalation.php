<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Alerts\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertEscalation extends BaseModel
{
    protected $table = 'alert_escalations';

    protected $fillable = [
        'alert_event_id',
        'escalation_level',
        'escalated_to_role',
        'escalated_at',
        'notified_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'escalation_level' => 'integer',
            'escalated_at' => 'datetime',
            'notified_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(AlertEvent::class, 'alert_event_id');
    }
}
