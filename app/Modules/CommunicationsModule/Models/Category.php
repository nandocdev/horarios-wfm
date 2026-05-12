<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Models;

use App\Modules\CoreModule\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Modelo para Categorías.
 *
 * Sistema de clasificación jerárquica para contenido de comunicaciones.
 */
class Category extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope para categorías activas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ordenar por sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Relación polimórfica con News.
     */
    public function news(): MorphToMany
    {
        return $this->morphedByMany(News::class, 'categorizable');
    }

    /**
     * Relación polimórfica con Polls.
     */
    public function polls(): MorphToMany
    {
        return $this->morphedByMany(Poll::class, 'categorizable');
    }

    /**
     * Relación polimórfica con Shoutouts.
     */
    public function shoutouts(): MorphToMany
    {
        return $this->morphedByMany(Shoutout::class, 'categorizable');
    }
}
