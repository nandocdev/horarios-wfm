<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Models;

use App\Modules\CommunicationsModule\Events\ReactionAdded;
use App\Modules\CommunicationsModule\Events\ReactionRemoved;
use App\Modules\CoreModule\Concerns\Auditable;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para Reacciones en Shoutouts.
 *
 * Permite likes y diferentes tipos de reacciones sociales.
 */
class Reaction extends Model
{
    use Auditable;

    protected $fillable = [
        'shoutout_id',
        'user_id',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Tipos de reacción disponibles.
     */
    public const TYPES = [
        'like' => '👍',
        'love' => '❤️',
        'celebrate' => '🎉',
        'support' => '🙌',
        'insightful' => '💡',
    ];

    /**
     * Shoutout al que pertenece la reacción.
     */
    public function shoutout(): BelongsTo
    {
        return $this->belongsTo(Shoutout::class);
    }

    /**
     * Usuario que hizo la reacción.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene el emoji correspondiente al tipo de reacción.
     */
    public function getEmojiAttribute(): string
    {
        return self::TYPES[$this->type] ?? '👍';
    }

    /**
     * Verifica si la reacción está activa.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Activa la reacción.
     */
    public function activate(): void
    {
        if (! $this->is_active) {
            $this->update(['is_active' => true]);
            event(new ReactionAdded($this));
        }
    }

    /**
     * Desactiva la reacción.
     */
    public function deactivate(): void
    {
        if ($this->is_active) {
            $this->update(['is_active' => false]);
            event(new ReactionRemoved($this));
        }
    }

    /**
     * Toggle de la reacción (activar/desactivar).
     */
    public function toggle(): void
    {
        $this->is_active ? $this->deactivate() : $this->activate();
    }

    /**
     * Scopes para consultas comunes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForShoutout($query, $shoutoutId)
    {
        return $query->where('shoutout_id', $shoutoutId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
