<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo de Etiqueta (Tag) para los artículos de la base de conocimiento.
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Tag extends Model
{
    protected $table = 'knowledge_tags';

    protected $fillable = [
        'name',
    ];

    /**
     * Relación con los artículos etiquetados.
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(
            KnowledgeArticle::class,
            'knowledge_article_tag',
            'tag_id',
            'article_id'
        );
    }
}
/**
 * [RIESGOS]
 * - Duplicados → Nombre con índice único en BD para evitar etiquetas duplicadas en inserciones concurrentes.
 */
