<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Models;

use App\Modules\CoreModule\Models\User;
use App\Shared\Support\Communications\HasContentState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Modelo para Encuestas.
 */
class Poll extends Model
{
    use HasContentState;

    protected $fillable = [
        'question',
        'options',
        'is_active',
        'expires_at',
        'scheduled_at',
        'archive_at',
        'reminder_sent_at',
        'status',
        'approved_by',
        'approved_at',
        'moderation_notes',
        'version_history',
    ];

    protected $casts = [
        'options' => 'array', // jsonb en Postgres
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'archive_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'version_history' => 'array',
    ];

    /**
     * Respuestas recibidas.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(PollResponse::class);
    }

    /**
     * Categorías de la encuesta.
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable');
    }

    /**
     * Tags de la encuesta.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Verifica si un usuario ya votó.
     */
    public function hasVoted(int $userId): bool
    {
        return $this->responses()->where('user_id', $userId)->exists();
    }

    /**
     * Scopes para estados de moderación.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Moderador que aprobó el contenido.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Registra un voto incrementando el contador en el JSON de opciones.
     */
    public function recordVote(string $answer): void
    {
        $options = $this->options;
        foreach ($options as &$option) {
            if ($option['text'] === $answer) {
                $option['votes'] = ($option['votes'] ?? 0) + 1;
                break;
            }
        }
        $this->options = $options;
        $this->save();
    }

    /**
     * Agrega entrada al historial de versiones.
     */
    protected function addToVersionHistory(): void
    {
        $changes = $this->getDirty();
        $history = $this->version_history ?? [];

        $history[] = [
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
            'changes' => $changes,
        ];

        $this->version_history = array_slice($history, -10); // Mantener últimas 10 versiones
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::updating(function ($poll) {
            if ($poll->isDirty()) {
                $poll->addToVersionHistory();
            }
        });
    }
}
