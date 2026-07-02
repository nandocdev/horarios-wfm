<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentAuditLog extends Model {
    protected $table = 'audit_logs';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'before',
        'after',
        'ip_address',
        'user_id',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'entity_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(
            'App\Modules\CoreModule\Models\User',
            'user_id'
        );
    }

    public function scopeFilter(Builder $query, array $filters): void {
        $query->when($filters['search'] ?? null, fn($q, $v) => $q->where(function ($sub) use ($v) {
            $sub->where('entity_type', 'ilike', "%{$v}%")
                ->orWhere('action', 'ilike', "%{$v}%")
                ->orWhere('ip_address', 'ilike', "%{$v}%");
        }))
            ->when($filters['action'] ?? null, fn($q, $v) => $q->where('action', $v))
            ->when($filters['entity_type'] ?? null, fn($q, $v) => $q->where('entity_type', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v));
    }
}
