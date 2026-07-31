<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Models;

use App\Modules\CoreModule\Models\User;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'before',
        'after',
        'ip_address',
        'user_id',
        'actor_name',
        'actor_email',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'entity_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function getActorLabelAttribute(): string
    {
        if ($this->user->exists) {
            return $this->user->name;
        }

        return $this->actor_name ?? 'Usuario eliminado';
    }

    protected static function newFactory(): AuditLogFactory
    {
        return AuditLogFactory::new();
    }

    public static function log(Model $model, string $action): self
    {
        $before = null;
        $after = null;

        if ($action === 'created') {
            $after = $model->toArray();
        } elseif ($action === 'updated') {
            $before = $model->getOriginal();
            $after = $model->toArray();
        } elseif ($action === 'deleted') {
            $before = $model->getOriginal();
        }

        $user = auth()->user();

        return static::create([
            'entity_type' => get_class($model),
            'entity_id' => $model->getKey(),
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'ip_address' => request()?->ip(),
            'user_id' => $user?->id,
            'actor_name' => $user?->name,
            'actor_email' => $user?->email,
        ]);
    }

    public function scopeFilter(Builder $query, array $filters): void
    {
        $query->when($filters['search'] ?? null, fn ($q, $v) => $q->where(function ($sub) use ($v) {
            $pattern = '%'.$v.'%';
            $sub->where('entity_type', 'ILIKE', $pattern)
                ->orWhere('action', 'ILIKE', $pattern)
                ->orWhere('ip_address', 'ILIKE', $pattern);
        }))
            ->when($filters['action'] ?? null, fn ($q, $v) => $q->where('action', $v))
            ->when($filters['entity_type'] ?? null, fn ($q, $v) => $q->where('entity_type', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
    }
}
