<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Models;

use App\Modules\CoreModule\Concerns\Auditable;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Modelo para Noticias.
 *
 * Maneja el contenido dinámico de la página de inicio.
 * Soporta MediaLibrary para imágenes, PDFs y vídeos.
 */
class News extends Model implements HasMedia
{
    use Auditable, InteractsWithMedia;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'author_id',
        'is_active',
        'published_at',
        'scheduled_at',
        'archive_at',
        'status',
        'approved_by',
        'approved_at',
        'moderation_notes',
        'version_history',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'archive_at' => 'datetime',
        'approved_at' => 'datetime',
        'version_history' => 'array',
    ];

    /**
     * Autor de la noticia.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Categorías de la noticia.
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable');
    }

    /**
     * Tags de la noticia.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Comentarios de la noticia.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Comentarios activos de la noticia.
     */
    public function activeComments(): HasMany
    {
        return $this->comments()->active()->orderBy('created_at', 'asc');
    }

    /**
     * Menciones en la noticia.
     */
    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    /**
     * Configuración de Media Collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('attachments')
            ->useDisk('public');
    }

    /**
     * Genera slugs automáticos si se requiere (u opcional).
     */
    protected static function booted(): void
    {
        static::creating(function ($news) {
            if (empty($news->slug)) {
                $news->slug = str($news->title)->slug()->toString().'-'.uniqid();
            }
        });

        static::updating(function ($news) {
            if ($news->isDirty()) {
                $news->addToVersionHistory();
            }
        });
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
     * Verifica si el contenido puede ser editado.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'pending_review']);
    }

    /**
     * Verifica si el contenido está publicado.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Envía a revisión.
     */
    public function submitForReview(): void
    {
        $this->update(['status' => 'pending_review']);
    }

    /**
     * Aprueba el contenido.
     */
    public function approve(User $moderator, ?string $notes = null): void
    {
        $this->update([
            'status' => 'published',
            'approved_by' => $moderator->id,
            'approved_at' => now(),
            'moderation_notes' => $notes,
        ]);
    }

    /**
     * Rechaza el contenido.
     */
    public function reject(User $moderator, string $notes): void
    {
        $this->update([
            'status' => 'draft',
            'approved_by' => $moderator->id,
            'approved_at' => now(),
            'moderation_notes' => $notes,
        ]);
    }

    /**
     * Archiva el contenido.
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
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
}
