<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Models;

use App\Modules\CommunicationsModule\Events\CommentCreated;
use App\Modules\CommunicationsModule\Events\CommentDeleted;
use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\Traits\Auditable;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para Comentarios en News.
 *
 * Permite interacciones sociales mediante comentarios anidados.
 */
class Comment extends Model
{
    use Auditable;

    protected $fillable = [
        'news_id',
        'user_id',
        'content',
        'parent_id',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Noticia a la que pertenece el comentario.
     */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    /**
     * Usuario que hizo el comentario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Comentario padre (para respuestas anidadas).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Respuestas a este comentario.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Verifica si es un comentario raíz (no tiene padre).
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Verifica si el comentario está activo.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Publica el comentario.
     */
    public function publish(): void
    {
        $this->update([
            'is_active' => true,
            'published_at' => now(),
        ]);

        event(new CommentCreated($this));
    }

    /**
     * Oculta el comentario.
     */
    public function hide(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Elimina el comentario y sus respuestas.
     */
    public function deleteWithReplies(): void
    {
        $this->replies()->delete();
        $this->delete();

        event(new CommentDeleted($this));
    }

    /**
     * Scopes para consultas comunes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForNews($query, $newsId)
    {
        return $query->where('news_id', $newsId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
