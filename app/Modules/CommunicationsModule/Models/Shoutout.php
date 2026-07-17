<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Models;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Support\Communications\HasContentState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Modelo para Shoutouts.
 *
 * Representa reconocimientos cortos entre colaboradores.
 */
class Shoutout extends Model implements HasMedia
{
    use HasContentState, InteractsWithMedia;

    protected $fillable = [
        'employee_id',
        'message',
        'is_active',
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
        'scheduled_at' => 'datetime',
        'archive_at' => 'datetime',
        'approved_at' => 'datetime',
        'version_history' => 'array',
    ];

    /**
     * Empleado reconocido.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Categorías del shoutout.
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable');
    }

    /**
     * Tags del shoutout.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Reacciones del shoutout.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Reacciones activas del shoutout.
     */
    public function activeReactions(): HasMany
    {
        return $this->reactions()->active();
    }

    /**
     * Menciones en el shoutout.
     */
    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
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
        static::updating(function ($shoutout) {
            if ($shoutout->isDirty()) {
                $shoutout->addToVersionHistory();
            }
        });
    }

    /**
     * Define las colecciones de medios.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Define las conversiones de imagen.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(225)
            ->sharpen(10);
    }
}
