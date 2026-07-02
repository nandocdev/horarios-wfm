<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Models;

use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Modelo para Tags.
 *
 * Sistema de etiquetado flexible para contenido de comunicaciones.
 */
class Tag extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope para tags activas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ordenar alfabéticamente.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Relación polimórfica con News.
     */
    public function news(): MorphToMany
    {
        return $this->morphedByMany(News::class, 'taggable');
    }

    /**
     * Relación polimórfica con Polls.
     */
    public function polls(): MorphToMany
    {
        return $this->morphedByMany(Poll::class, 'taggable');
    }

    /**
     * Relación polimórfica con Shoutouts.
     */
    public function shoutouts(): MorphToMany
    {
        return $this->morphedByMany(Shoutout::class, 'taggable');
    }
}
