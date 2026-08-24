<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Alerts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertRule extends Model
{
    protected $table = 'alert_rules';

    protected $fillable = [
        'event_type',
        'label',
        'description',
        'is_enabled',
        'threshold_seconds',
        'escalation_minutes',
        'escalation_roles',
        'channels',
        'cooldown_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'threshold_seconds' => 'integer',
            'escalation_minutes' => 'array',
            'escalation_roles' => 'array',
            'channels' => 'array',
            'cooldown_minutes' => 'integer',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(AlertEvent::class, 'alert_rule_id');
    }

    public function activeEvents(): HasMany
    {
        return $this->hasMany(AlertEvent::class, 'alert_rule_id')
            ->whereNull('resolved_at');
    }
}
