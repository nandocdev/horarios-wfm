<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Models;

use App\Modules\CommunicationsModule\Events\MentionCreated;
use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\Traits\Auditable;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Modelo para Menciones (@usuario).
 *
 * Sistema de menciones polimórfico para notificar usuarios mencionados.
 */
class Mention extends Model
{
    use Auditable;

    protected $fillable = [
        'mentioned_user_id',
        'mentioner_user_id',
        'mentionable_type',
        'mentionable_id',
        'context',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Usuario mencionado.
     */
    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }

    /**
     * Usuario que hizo la mención.
     */
    public function mentionerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioner_user_id');
    }

    /**
     * Entidad donde se hizo la mención (News, Comment, Shoutout, etc.).
     */
    public function mentionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Marca la mención como leída.
     */
    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Verifica si la mención ha sido leída.
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * Scopes para consultas comunes.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('mentioned_user_id', $userId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('mentioner_user_id', $userId);
    }

    public function scopeInContent($query, $type, $id)
    {
        return $query->where('mentionable_type', $type)->where('mentionable_id', $id);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::created(function ($mention) {
            event(new MentionCreated($mention));
        });
    }
}
