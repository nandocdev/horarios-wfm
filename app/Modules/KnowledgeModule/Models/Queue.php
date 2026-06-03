<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo de Cola con su correspondiente prioridad para ordenar búsquedas.
 *
 * @property int $id
 * @property string $name
 * @property int $priority
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Queue extends Model
{
    protected $table = 'knowledge_queues';

    protected $fillable = [
        'name',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relación con los artículos asignados a esta cola.
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(
            Article::class,
            'knowledge_article_queue',
            'queue_id',
            'article_id'
        );
    }
}
/**
 * [RIESGOS]
 * - Prioridad no delimitada → Se asume rango entero >= 0. Cuidado al ingresar valores negativos en BD.
 */
