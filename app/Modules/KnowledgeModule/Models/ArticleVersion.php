<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Models;

use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para registrar las versiones históricas del contenido de un artículo.
 *
 * @property int $id
 * @property int $article_id
 * @property int $version
 * @property string $content
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 */
class ArticleVersion extends Model
{
    protected $table = 'knowledge_article_versions';

    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'version',
        'content',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Relación con el artículo principal.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * Relación con el usuario creador de esta versión.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
/**
 * [RIESGOS]
 * - Crecimiento masivo → Si se edita frecuentemente, esta tabla puede crecer rápidamente. Debería considerarse un job de purga o límite si es necesario.
 */
